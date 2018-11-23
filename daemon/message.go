package daemon

import (
	"strconv"
	"time"
)

type MessageType int

func (m MessageType) String() string {
	switch m {
	case PHPProcessInit:
		return "process init"
	case PHPProcessShutdown:
		return "process shutdown"
	case PHPRequestInit:
		return "request init"
	case PHPRequestShutdown:
		return "request shutdown"

	case TraceExport:
		return "trace export"

	case MeasureCreate:
		return "create measure"
	case ViewReportingPeriod:
		return "reporting period"
	case ViewRegister:
		return "register view"
	case ViewUnregister:
		return "unregister view"
	case StatsRecord:
		return "record stats"

	default:
		return strconv.Itoa(int(m))
	}
}

// php process / request types (1 - 19)
const (
	PHPProcessInit MessageType = iota + 1
	PHPProcessShutdown
	PHPRequestInit
	PHPRequestShutdown
)

// trace types (20 - 39)
const (
	TraceExport MessageType = iota + 20
)

// stats types (40 - ...)
const (
	MeasureCreate MessageType = iota + 40
	ViewReportingPeriod
	ViewRegister
	ViewUnregister
	StatsRecord
)

type MeasurementType int

// measurement value types
const (
	TypeInt MeasurementType = iota + 1
	TypeFloat
	TypeUnknown = 255
)

type AggregationType int

// aggregation types
const (
	None AggregationType = iota
	Count
	Sum
	Distribution
	LastValue
)

// Message holds an incoming message header and raw data payload.
type Message struct {
	Type        MessageType
	SequenceNr  uint64
	ProcessID   uint64
	ThreadID    uint64
	StartTime   time.Time
	ReceiveTime time.Time
	MsgLen      uint64
	Float32     bool
	RawPayload  []byte
}

// AppendData appends raw Message data to the internal Message buffer.
// It will return the remainder after the append action. A negative remainder
// signals that the provided data holds more information than needed to complete
// the Message. A positive remainder signals the amount of data still needed to
// complete the Message.
func (mh *Message) AppendData(data []byte) int {
	var (
		msgLen    = int(mh.MsgLen)
		offset    = len(mh.RawPayload)
		remainder = msgLen - offset - len(data)
	)
	if remainder < 0 {
		// truncate data as it holds more then we need
		data = data[0 : msgLen-offset]
	}

	// re-slice RawPayload to fit additional data
	mh.RawPayload = mh.RawPayload[0 : offset+len(data)]
	copy(mh.RawPayload[offset:], data)

	return remainder
}
