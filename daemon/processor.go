package daemon

import (
	"encoding/binary"
	"fmt"

	"github.com/go-kit/kit/log"
	"github.com/go-kit/kit/log/level"
	"go.opencensus.io/stats"
)

type Processor struct {
	Logger   log.Logger
	Messages chan *Message
}

func (p *Processor) Run() error {
	for m := range p.Messages {
		_ = level.Debug(p.Logger).Log("msg", "processing message", "type", m.Type)
		switch m.Type {
		case measureCreate:
			if !p.createMeasure(m) {
				panic("NOOOOOO!!!!")
			}
		}
	}
	return nil
}

// Process sends our Message on the internal channel to be processed.
// Returns true on successful ingestion, false on high water mark.
func (p *Processor) Process(m *Message) bool {
	select {
	case p.Messages <- m:
		return true
	default:
		_ = level.Error(p.Logger).Log("msg", "channel buffer full, dropping message")
		return false
	}
}

// Close shuts down our Processor.
func (p *Processor) Close() error {
	close(p.Messages)
	return nil
}

func (p *Processor) createMeasure(m *Message) bool {
	defer func() {
		if recover() != nil {
			_ = level.Warn(p.Logger).Log("msg", "invalid message payload encountered")
		}
	}()

	mType := measurementType(m.rawPayload[0])
	idx := 1

	name, n := decodeString(m.rawPayload[idx:])
	if n < 0 {
		_ = level.Warn(p.Logger).Log("msg", "invalid message payload encountered")
		return false
	}

	description, n := decodeString(m.rawPayload[idx:])
	if n < 0 {
		_ = level.Warn(p.Logger).Log("msg", "invalid message payload encountered")
		return false
	}

	unit, n := decodeString(m.rawPayload[idx:])
	if n < 0 {
		_ = level.Warn(p.Logger).Log("msg", "invalid message payload encountered")
		return false
	}

	switch mType {
	case typeInt:
		m := stats.Int64(name, description, unit)
		fmt.Printf("INT MEASURE: %+v\n", m)
		return true
	case typeFloat:
		m := stats.Float64(name, description, unit)
		fmt.Printf("FLOAT MEASURE: %+v\n", m)
		return true
	default:
		_ = level.Debug(p.Logger).Log("msg", "unknown measure type", "type", int(mType))
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
