package main

import (
	"flag"
	"fmt"
	"net"
	"net/http"
	"os"
	"os/signal"
	"syscall"

	"github.com/go-kit/kit/log"
	"github.com/go-kit/kit/log/level"
	"github.com/oklog/run"
	"go.opencensus.io/zpages"

	"github.com/census-instrumentation/opencensus-php/daemon"
	"github.com/census-instrumentation/opencensus-php/daemon/processor/local"
	"github.com/census-instrumentation/opencensus-php/daemon/unixsocket"
)

const (
	logNone = iota
	logError
	logWarn
	logInfo
	logDebug

	// defaultOCAgentAddr      = "localhost:55678"
	defaultUnixSocketPath   = "/tmp/ocdaemon.sock"
	defaultLogLevel         = logError
	defaultMsgBufSize       = 1000
	defaultZPagesAddr       = ":8888"
	defaultZPagesPathPrefix = "/debug"
)

var (
	serviceName  = "OCDaemon-PHP"
	buildVersion string
	buildDate    string
)

func main() {
	var (
		fs = flag.NewFlagSet("", flag.ExitOnError)
		// flagOCAgentAddr    = fs.String("ocagent.addr", defaultOCAgentAddr, "host:port of the OC Agent service")
		flagLogLevel         = fs.Int("log.level", defaultLogLevel, "logging level")
		flagMsgBufSize       = fs.Int("msg.bufsize", defaultMsgBufSize, "size of buffered message channel")
		flagZPagesAddr       = fs.String("zpages.addr", defaultZPagesAddr, "zPages bind address")
		flagZPagesPathPrefix = fs.String("zpages.path", defaultZPagesPathPrefix, "zPages path prefix")
		flagUnixSocketPath   = fs.String("socket.path", defaultUnixSocketPath, "unix socket path to listen on")
		flagVersion          = fs.Bool("version", false, "show version information")

		processor      daemon.Processor
		closeRequested bool
	)

	// parse our command line override flags
	if err := fs.Parse(os.Args[1:]); err != nil {
		_, _ = fmt.Fprintf(os.Stderr, "%v", err)
		os.Exit(1)
	}

	if *flagVersion {
		fmt.Printf(
			"Version information for: %s\nBuild     : %s\nBuild date: %s\n\n",
			serviceName,
			buildVersion,
			buildDate,
		)
		os.Exit(0)
	}

	var logger log.Logger
	{
		logger = log.NewLogfmtLogger(log.NewSyncWriter(os.Stderr))
		switch *flagLogLevel {
		case logNone:
			logger = log.NewNopLogger()
		case logError:
			logger = level.NewFilter(logger, level.AllowError())
		case logWarn:
			logger = level.NewFilter(logger, level.AllowWarn())
		case logInfo:
			logger = level.NewFilter(logger, level.AllowInfo())
		case logDebug:
			logger = level.NewFilter(logger, level.AllowDebug())
		default:
			_, _ = fmt.Fprintf(os.Stderr, "invalid log level %d\n", *flagLogLevel)
			os.Exit(1)
		}
		logger = log.With(logger,
			"svc", serviceName,
			"ts", log.DefaultTimestampUTC,
			"clr", log.DefaultCaller,
		)

	}

	_ = level.Info(logger).Log("msg", "service started")
	defer func() {
		_ = level.Info(logger).Log("msg", "service ended")
	}()

	var g run.Group
	if *flagZPagesAddr != "" {
		// set-up zPages
		var (
			l   net.Listener
			err error
			mux = http.NewServeMux()
		)
		zpages.Handle(mux, *flagZPagesPathPrefix)
		g.Add(func() error {
			l, err = net.Listen("tcp", *flagZPagesAddr)
			if err != nil {
				return err
			}
			return http.Serve(l, mux)
		}, func(error) {
			_ = l.Close()
		})
	}
	{
		// set-up the message processor
		if *flagMsgBufSize < 100 {
			*flagMsgBufSize = 1000
		}
		processor = local.New(*flagMsgBufSize, logger)

		g.Add(func() error {
			return processor.Run()
		}, func(error) {
			_ = processor.Close()
		})
	}
	{
		// set-up the unix socket service
		hnd := &daemon.ConnectionHandler{Logger: logger, Processor: processor}
		uss := unixsocket.New(*flagUnixSocketPath, hnd)

		g.Add(func() error {
			return uss.ListenAndServe()
		}, func(error) {
			_ = uss.Close()
		})
	}
	{
		// set-up our signal handler
		var (
			cIntr = make(chan struct{})
			cSig  = make(chan os.Signal, 2)
		)
		defer close(cSig)

		g.Add(func() error {
			signal.Notify(cSig, syscall.SIGINT, syscall.SIGTERM)
			select {
			case sig := <-cSig:
				closeRequested = true
				_ = level.Info(logger).Log("msg", "shutdown requested", "signal", sig)
				return fmt.Errorf("received signal %s", sig)
			case <-cIntr:
				return nil
			}
		}, func(error) {
			close(cIntr)
		})
	}

	// spawn the run group goroutines and wait for shutdown
	err := g.Run()

	if !closeRequested {
		_ = level.Error(logger).Log("exit", err)
	}
}
