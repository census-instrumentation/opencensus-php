package local

import (
	"context"
	"encoding/binary"
	"math"
	"sync"
	"time"

	"github.com/go-kit/kit/log"
	"github.com/go-kit/kit/log/level"
	"go.opencensus.io/exemplar"
	"go.opencensus.io/stats"
	"go.opencensus.io/stats/view"
	"go.opencensus.io/tag"

	"github.com/census-instrumentation/opencensus-php/daemon"
)

type measurement struct {
	name  string
	mType daemon.MeasurementType
	val   interface{}
}

type Processor struct {
	mu       sync.RWMutex
	logger   log.Logger
	messages chan *daemon.Message

	measures map[string]stats.Measure
}

func New(msgBufSize int, l log.Logger) *Processor {
	return &Processor{
		logger:   l,
		messages: make(chan *daemon.Message, msgBufSize),
		measures: make(map[string]stats.Measure),
	}
}

func (p *Processor) Run() error {
	for m := range p.messages {
		_ = level.Debug(p.logger).Log("msg", "processing message", "type", m.Type)
		switch m.Type {
		case daemon.MeasureCreate:
			p.createMeasure(m)
		case daemon.ViewReportingPeriod:
			p.reportingPeriod(m)
		case daemon.ViewRegister:
			p.registerView(m)
		case daemon.ViewUnregister:
			p.unregisterView(m)
		case daemon.StatsRecord:
			p.recordStats(m)
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
		p.mu.Lock()
		if _, ok := p.measures[name]; !ok {
			p.measures[name] = stats.Int64(name, description, unit)
		}
		p.mu.Unlock()
		return true
	case daemon.TypeFloat:
		p.mu.Lock()
		if _, ok := p.measures[name]; !ok {
			p.measures[name] = stats.Float64(name, description, unit)
		}
		p.mu.Unlock()
		return true
	default:
		_ = level.Debug(p.logger).Log("msg", "unknown measure type", "type", int(mType))
		return false
	}
}

func (p *Processor) reportingPeriod(m *daemon.Message) bool {
	defer func() {
		if recover() != nil {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		}
	}()

	f, _ := decodeFloat(m, m.RawPayload)
	msec := time.Duration(f * 1e3)
	view.SetReportingPeriod(msec * time.Millisecond)

	return true
}

func (p *Processor) registerView(m *daemon.Message) bool {
	var (
		n     int
		ok    bool
		err   error
		views []*view.View
	)

	defer func() {
		if recover() != nil {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		}
	}()

	viewCount, idx := binary.Uvarint(m.RawPayload)
	if idx < 1 {
		_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		return false
	}

	for i := uint64(0); i < viewCount; i++ {
		v := &view.View{}
		v.Name, n = decodeString(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		idx += n

		v.Description, n = decodeString(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		idx += n

		tagKeyCount, n := binary.Uvarint(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		idx += n

		for j := uint64(0); j < tagKeyCount; j++ {
			name, n := decodeString(m.RawPayload[idx:])
			if n < 1 {
				_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
				return false
			}
			idx += n

			tagKey, err := tag.NewKey(name)
			if err != nil {
				_ = level.Error(p.logger).Log("msg", "register views failed on creating tag key", "err", err)
				return false
			}
			v.TagKeys = append(v.TagKeys, tagKey)
		}

		measureName, n := decodeString(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		idx += n

		p.mu.RLock()
		v.Measure, ok = p.measures[measureName]
		p.mu.RUnlock()

		if !ok {
			_ = level.Error(p.logger).Log("msg", "register views failed on unknown measure", "err", err)
			return false
		}

		v.Aggregation = &view.Aggregation{}
		aggregationType, n := binary.Uvarint(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		v.Aggregation.Type = view.AggType(aggregationType)
		idx += n

		if daemon.AggregationType(aggregationType) == daemon.Distribution {
			boundaryCount, n := binary.Uvarint(m.RawPayload[idx:])
			if n < 1 {
				_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
				return false
			}
			idx += n

			for k := uint64(0); k < boundaryCount; k++ {
				boundary, n := decodeFloat(m, m.RawPayload[idx:])
				v.Aggregation.Buckets = append(v.Aggregation.Buckets, boundary)
				idx += n
			}
		}
		views = append(views, v)
	}

	if err := view.Register(views...); err != nil {
		_ = level.Error(p.logger).Log("msg", "register views failed.", "err", err)
		return false
	}
	return true
}

func (p *Processor) unregisterView(m *daemon.Message) bool {
	defer func() {
		if recover() != nil {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		}
	}()

	viewCount, idx := binary.Uvarint(m.RawPayload)
	if idx < 1 || viewCount == 0 {
		_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
	}

	var views []*view.View
	for i := uint64(0); i < viewCount; i++ {
		name, n := decodeString(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		}
		views = append(views, &view.View{Name: name})
	}
	view.Unregister(views...)

	return true
}

func (p *Processor) recordStats(m *daemon.Message) bool {
	var (
		n            int
		mType        uint64
		measurements []*measurement
		mutators     []tag.Mutator
		attachments  = make(exemplar.Attachments)
		ms           []stats.Measurement
	)

	defer func() {
		if recover() != nil {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		}
	}()

	measurementCount, idx := binary.Uvarint(m.RawPayload)
	if idx < 1 {
		_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		return false
	}

	for i := uint64(0); i < measurementCount; i++ {
		ms := &measurement{}
		ms.name, n = decodeString(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		idx += n

		mType, n = binary.Uvarint(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		ms.mType = daemon.MeasurementType(mType)
		idx += n

		switch ms.mType {
		case daemon.TypeInt:
			ms.val, n = binary.Uvarint(m.RawPayload[idx:])
		case daemon.TypeFloat:
			ms.val, n = decodeFloat(m, m.RawPayload[idx:])
		default:
			_ = level.Warn(p.logger).Log("msg", "unknown measurement type encountered", "type", ms.mType)
		}
		idx += n
		measurements = append(measurements, ms)
	}

	tagCount, n := binary.Uvarint(m.RawPayload[idx:])
	if n < 1 {
		_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		return false
	}
	idx += n

	for i := uint64(0); i < tagCount; i++ {
		key, n := decodeString(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		idx += n

		value, n := decodeString(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		idx += n

		tagKey, err := tag.NewKey(key)
		if err != nil {
			_ = level.Error(p.logger).Log("msg", "invalid tag payload encountered", "err", err)
			return false
		}
		mutators = append(mutators, tag.Insert(tagKey, value))
	}

	attachmentCount, n := binary.Uvarint(m.RawPayload[idx:])
	if n < 1 {
		_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
		return false
	}
	idx += n

	for i := uint64(0); i < attachmentCount; i++ {
		key, n := decodeString(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		idx += n

		value, n := decodeString(m.RawPayload[idx:])
		if n < 1 {
			_ = level.Warn(p.logger).Log("msg", "invalid message payload encountered")
			return false
		}
		idx += n
		attachments[key] = value
	}

	ctx := context.Background()

	if len(attachments) > 0 {
		ctx = daemon.AttachmentsToContext(ctx, attachments)
	}

	ms = p.processMeasurement(measurements)
	if len(ms) == 0 {
		return false
	}

	if err := stats.RecordWithTags(ctx, mutators, ms...); err != nil {
		_ = level.Error(p.logger).Log("msg", "invalid tags in record context", "err", err)
		return false
	}

	return true
}

func (p *Processor) processMeasurement(ms []*measurement) []stats.Measurement {
	var measurements []stats.Measurement

	p.mu.RLock()
	defer p.mu.RUnlock()

	for _, m := range ms {
		mm, ok := p.measures[m.name]
		if !ok {
			continue
		}
		switch m.mType {
		case daemon.TypeInt:
			if im, ok := mm.(stats.Int64Measure); ok {
				measurements = append(measurements, im.M(m.val.(int64)))
			}
		case daemon.TypeFloat:
			if fm, ok := mm.(stats.Float64Measure); ok {
				measurements = append(measurements, fm.M(m.val.(float64)))
			}
		}
	}

	return measurements
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

func decodeFloat(m *daemon.Message, buf []byte) (float64, int) {
	if m.Float32 {
		return float64(math.Float32frombits(binary.BigEndian.Uint32(buf))), 4
	}
	return math.Float64frombits(binary.BigEndian.Uint64(buf)), 8
}
