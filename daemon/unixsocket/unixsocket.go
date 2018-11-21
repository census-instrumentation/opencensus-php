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
