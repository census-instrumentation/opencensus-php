package daemon

import (
	"time"

	"gitlab.xacte.com/platform/utils/tag"
	"go.opencensus.io/exemplar"
	"go.opencensus.io/stats"
	"go.opencensus.io/stats/view"
	"go.opencensus.io/trace"
)

type msgType int
type measurementType int

// php process / request types (1 - 19)
const (
	phpProcessInit msgType = iota + 1
	phpProcessShutdown
	phpRequestInit
	phpRequestShutdown
)

// trace types (20 - 39)
const (
	traceExport msgType = iota + 20
)

// stats types (40 - ...)
const (
	measureCreate msgType = iota + 40
	viewReportingPeriod
	viewRegister
	viewUnregister
	statsRecord
)

// measurement value types
const (
	typeInt measurementType = iota + 1
	typeFloat
	typeUnknown = 255
)

type Message interface{}

type message struct {
	Type        msgType
	SequenceNr  uint64
	ProcessID   uint64
	ThreadID    uint64
	StartTime   time.Time
	ReceiveTime time.Time
	MsgLen      uint64
	rawPayload  []byte
}

type processInit struct {
	message
}

type processShutdown struct {
	message
}

type requestInit struct {
	message
	ProtocolVersion int
	PHPVersion      string
	ZendVersion     string
}

type requestShutdown struct {
	message
}

type exportedSpans struct {
	message
	Spans struct {
		TraceID             trace.TraceID
		SpanID              trace.SpanID
		ParentSpanID        trace.SpanID
		Name                string
		Kind                int
		StackTrace          interface{}
		StartTime           time.Time
		EndTime             time.Time
		Status              trace.Status
		Attributes          []trace.Attribute
		TimeEvents          []trace.MessageEvent
		Links               []trace.Link
		SameProcessAsParent bool
	}
}

type measure struct {
	message
	Type    measurementType
	Measure stats.Measure
}

type reportingPeriod struct {
	message
	Interval time.Duration
}

type registerView struct {
	message
	Views []view.View
}

type unregisterView struct {
	message
	Names []string
}

type recordStats struct {
	message
	Measurements []stats.Measurement
	Tags         tag.Tags
	Attachements exemplar.Attachments
}

func (mh message) ParseMessage() Message {
	mh.ReceiveTime = time.Now()

	switch mh.Type {
	case phpProcessInit:
		return &processInit{
			message: mh,
		}
	case phpProcessShutdown:
		return &processShutdown{
			message: mh,
		}
	case phpRequestInit:
		return &requestInit{
			message: mh,
		}
	case phpRequestShutdown:
		return &requestShutdown{
			message: mh,
		}
	case traceExport:
		return &exportedSpans{
			message: mh,
		}
	case measureCreate:
		return &measure{
			message: mh,
		}
	case viewReportingPeriod:
		return &reportingPeriod{
			message: mh,
		}
	case viewRegister:
		return &registerView{
			message: mh,
		}
	case viewUnregister:
		return &unregisterView{
			message: mh,
		}
	case statsRecord:
		return &recordStats{
			message: mh,
		}
	default:
		return nil
	}
}

// AppendData appends raw message data to the internal message buffer.
// It will return the remainder after the append action. A negative remainder
// signals data holding more information than needed to complete the message. A
// positive remainder signals the amount of data still needed to complete the
// message.
func (mh *message) AppendData(data []byte) int {
	var (
		msgLen    = int(mh.MsgLen)
		offset    = len(mh.rawPayload)
		remainder = msgLen - offset - len(data)
	)
	if remainder < 0 {
		// truncate data as it holds more then we need
		data = data[0 : msgLen-offset]
	}

	// re-slice rawPayload to fit additional data
	mh.rawPayload = mh.rawPayload[0 : offset+len(data)]
	copy(mh.rawPayload[offset:], data)

	return remainder
}
