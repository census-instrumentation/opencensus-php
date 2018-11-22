package local

import (
	"encoding/binary"
	"sync"

	"github.com/davecgh/go-spew/spew"
	"github.com/go-kit/kit/log"
	"github.com/go-kit/kit/log/level"
	"go.opencensus.io/stats"
	"go.opencensus.io/stats/view"

	"github.com/census-instrumentation/opencensus-php/daemon"
)

type Processor struct {
	mu       sync.Mutex
	logger   log.Logger
	messages chan *daemon.Message

	measures map[string]stats.Measure
	views    map[string]view.View
}

func New(msgBufSize int, l log.Logger) *Processor {
	return &Processor{
		logger:   l,
		messages: make(chan *daemon.Message, msgBufSize),
	}
}

func (p *Processor) Run() error {
	for m := range p.messages {
		_ = level.Debug(p.logger).Log("msg", "processing message", "type", m.Type)
		switch m.Type {
		case daemon.MeasureCreate:
			p.createMeasure(m)
		}
	}
	return nil
}

// Process sends our Message on the internal channel to be processed.
// Returns true on successful ingestion, false on high water mark.
func (p *Processor) Process(m *daemon.Message) bool {
	select {
	case p.messages <- m:
		return true
	default:
		_ = level.Error(p.logger).Log("msg", "channel buffer full, dropping message")
		return false
	}
}

// Close shuts down our Processor.
func (p *Processor) Close() error {
	close(p.messages)
	return nil
}

func (p *Processor) createMeasure(m *daemon.Message) bool {
	defer func() {
		if recover() != nil {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		}
	}()

	mType := daemon.MeasurementType(m.RawPayload[0])
	idx := 1

	name, n := decodeString(m.RawPayload[idx:])
	if n < 0 {
		_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		return false
	}
	idx += n

	description, n := decodeString(m.RawPayload[idx:])
	if n < 0 {
		_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		return false
	}
	idx += n

	unit, n := decodeString(m.RawPayload[idx:])
	if n < 0 {
		_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		return false
	}

	switch mType {
	case daemon.TypeInt:
		m := stats.Int64(name, description, unit)
		spew.Dump(m)
		return true
	case daemon.TypeFloat:
		m := stats.Float64(name, description, unit)
		spew.Dump(m)
		return true
	default:
		_ = level.Debug(p.logger).Log("msg", "unknown measure type", "type", int(mType))
		return false
	}
}

func decodeString(buf []byte) (string, int) {
	if len(buf) < 1 {
		return "", -1
	}
	l, n := binary.Uvarint(buf)
	i := int(l) + n
	if n < 1 || len(buf) < i {
		return "", -1
	}

	return string(buf[n:i]), i
}
