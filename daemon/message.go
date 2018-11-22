package daemon

import (
	"strconv"
	"time"
)

type messageType int

func (m messageType) String() string {
	switch m {
	case phpProcessInit:
		return "process init"
	case phpProcessShutdown:
		return "process shutdown"
	case phpRequestInit:
		return "request init"
	case phpRequestShutdown:
		return "request shutdown"

	case traceExport:
		return "trace export"

	case measureCreate:
		return "create measure"
	case viewReportingPeriod:
		return "reporting period"
	case viewRegister:
		return "register view"
	case viewUnregister:
		return "unregister view"
	case statsRecord:
		return "record stats"

	default:
		return strconv.Itoa(int(m))
	}
}

type measurementType int

// php process / request types (1 - 19)
const (
	phpProcessInit messageType = iota + 1
	phpProcessShutdown
	phpRequestInit
	phpRequestShutdown
)

// trace types (20 - 39)
const (
	traceExport messageType = iota + 20
)

// stats types (40 - ...)
const (
	measureCreate messageType = iota + 40
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

// Message holds an incoming message header and raw data payload.
type Message struct {
	Type        messageType
	SequenceNr  uint64
	ProcessID   uint64
	ThreadID    uint64
	StartTime   time.Time
	ReceiveTime time.Time
	MsgLen      uint64
	rawPayload  []byte
}

// AppendData appends raw Message data to the internal Message buffer.
// It will return the remainder after the append action. A negative remainder
// signals that the provided data holds more information than needed to complete
// the Message. A positive remainder signals the amount of data still needed to
// complete the Message.
func (mh *Message) AppendData(data []byte) int {
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
