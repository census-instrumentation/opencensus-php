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

package unixsocket

import (
	"net"

	"github.com/census-instrumentation/opencensus-php/daemon"
)

// Server is our UnixSocket server.
type Server struct {
	a *net.UnixAddr
	l *net.UnixListener
	h daemon.Handler
}

// New returns a new Unix Socket Server.
func New(socketPath string, h daemon.Handler) *Server {
	return &Server{
		a: &net.UnixAddr{
			Name: socketPath,
			Net:  "unix",
		},
		h: h,
	}
}

// ListenAndServe on a Unix Socket.
func (s *Server) ListenAndServe() (err error) {
	s.l, err = net.ListenUnix("unix", s.a)
	if err != nil {
		return err
	}
	for {
		c, err := s.l.AcceptUnix()
		if err != nil {
			return err
		}
		go s.h.Handle(c)
	}
}

// Close implements io.Closer
func (s *Server) Close() error {
	return s.l.Close()
}
