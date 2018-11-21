package main

import (
	"flag"
	"fmt"
	"os"
	"os/signal"
	"syscall"

	"github.com/go-kit/kit/log"
	"github.com/go-kit/kit/log/level"
	"github.com/oklog/run"

	"github.com/census-instrumentation/opencensus-php/daemon"
	"github.com/census-instrumentation/opencensus-php/daemon/unixsocket"
)

const (
	logNone = iota
	logError
	logWarn
	logInfo
	logDebug

	defaultOCAgentAddr    = "localhost:55678"
	defaultUnixSocketPath = "/tmp/ocdaemon.sock"
	defaultLogLevel       = logError
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
		flagLogLevel       = fs.Int("log.level", defaultLogLevel, "logging level")
		flagUnixSocketPath = fs.String("socket.path", defaultUnixSocketPath, "unix socket path to listen on")
		flagVersion        = fs.Bool("version", false, "show version information")

		closeRequested bool
	)

	// parse our command line override flags
	if err := fs.Parse(os.Args[1:]); err != nil {
		fmt.Fprintf(os.Stderr, "%v", err)
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
			fmt.Fprintf(os.Stderr, "invalid log level %d\n", *flagLogLevel)
			os.Exit(1)
		}
		logger = log.With(logger,
			"svc", serviceName,
			"ts", log.DefaultTimestampUTC,
			"clr", log.DefaultCaller,
		)

	}

	level.Info(logger).Log("msg", "service started")
	defer level.Info(logger).Log("msg", "service ended")

	var g run.Group
	{
		// set-up our unix socket service
		hnd := &daemon.ConnectionHandler{Logger: logger}
		us := unixsocket.New(*flagUnixSocketPath, hnd)

		g.Add(func() error {
			return us.ListenAndServe()
		}, func(error) {
			us.Close()
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
				level.Info(logger).Log("msg", "shutdown requested", "signal", sig)
				return fmt.Errorf("received signal %s", sig)
			case <-cIntr:
				return nil
			}
		}, func(error) {
			close(cIntr)
		})
	}

	// spawn our goroutines and wait for shutdown
	err := g.Run()

	if !closeRequested {
		level.Error(logger).Log("exit", err)
	}
}
