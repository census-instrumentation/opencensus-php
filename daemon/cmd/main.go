// Copyright 2018, OpenCensus Authors
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

package main

import (
	"flag"
	"fmt"
	"net"
	"net/http"
	"os"
	"os/signal"
	"syscall"

	"contrib.go.opencensus.io/exporter/ocagent"
	"github.com/go-kit/kit/log"
	"github.com/go-kit/kit/log/level"
	"github.com/oklog/run"
	"go.opencensus.io/stats/view"
	"go.opencensus.io/trace"
	"go.opencensus.io/zpages"

	"github.com/census-instrumentation/opencensus-php/daemon"
	"github.com/census-instrumentation/opencensus-php/daemon/processor/local"
	"github.com/census-instrumentation/opencensus-php/daemon/unixsocket"
)

const (
	serviceName = "OCDaemon-PHP"

	defaultOCAgentAddr      = "localhost:55678"
	defaultUnixSocketPath   = "/tmp/ocdaemon.sock"
	defaultLogLevel         = logError
	defaultMsgBufSize       = 1000
	defaultZPagesAddr       = ":8888"
	defaultZPagesPathPrefix = "/debug"
)

const (
	logNone = iota
	logError
	logWarn
	logInfo
	logDebug
)

var (
	buildVersion string
	buildDate    string
)

func main() {
	var (
		fs                   = flag.NewFlagSet("", flag.ExitOnError)
		flagOCAgentAddr      = fs.String("ocagent.addr", defaultOCAgentAddr, "Address of the OpenCensus Agent")
		flagServiceName      = fs.String("php.servicename", os.Getenv("HOSTNAME"), "Name of our PHP service")
		flagLogLevel         = fs.Int("log.level", defaultLogLevel, "Logging level to use")
		flagMsgBufSize       = fs.Int("msg.bufsize", defaultMsgBufSize, "Size of buffered message channel")
		flagZPagesAddr       = fs.String("zpages.addr", defaultZPagesAddr, "zPages bind address")
		flagZPagesPathPrefix = fs.String("zpages.path", defaultZPagesPathPrefix, "zPages path prefix")
		flagUnixSocketPath   = fs.String("socket.path", defaultUnixSocketPath, "Unix socket path to listen on")
		flagVersion          = fs.Bool("version", false, "Show version information")

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
			logger = level.NewFilter(logger, level.AllowError())
		}
		logger = log.With(logger,
			"svc", serviceName,
			"ts", log.DefaultTimestampUTC,
			"clr", log.DefaultCaller,
		)

	}

	_ = level.Info(logger).Log("msg", "service started", "agent", *flagOCAgentAddr)
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
		l, err = net.Listen("tcp", *flagZPagesAddr)

		if err != nil {
			_ = level.Error(logger).Log("msg", "failed to bind to requested zPages address", "err", err)
			os.Exit(1)
		}
		g.Add(func() error {
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

		if *flagServiceName == "" {
			*flagServiceName = serviceName
		}

		agentExporter, err := ocagent.NewExporter(
			ocagent.WithInsecure(),
			ocagent.WithAddress(*flagOCAgentAddr),
			ocagent.WithServiceName(*flagServiceName),
		)
		if err != nil {
			_ = level.Error(logger).Log("msg", "failed to create OCAgent exporter", "err", err)
			os.Exit(1)
		}

		// enables Daemon internal traces
		trace.RegisterExporter(agentExporter)

		// enables Daemon and PHP Process Views
		view.RegisterExporter(agentExporter)

		// provide agent as exporter for proxied spans
		processor = local.New(*flagMsgBufSize, []trace.Exporter{agentExporter}, logger)

		g.Add(func() error {
			return processor.Run()
		}, func(error) {
			_ = processor.Close()
			agentExporter.Flush()
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
		_ = level.Error(logger).Log("msg", "unexpected shutdown of service", "err", err)
	}
}
