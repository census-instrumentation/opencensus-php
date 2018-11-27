package local

import (
	"bytes"
	"encoding/json"
	"time"

	"github.com/census-instrumentation/opencensus-php/daemon"
)

type measurement struct {
	name  string
	mType daemon.MeasurementType
	val   interface{}
}

type span struct {
	TraceID      string      `json:"traceId"`
	SpanID       string      `json:"spanId"`
	ParentSpanID string      `json:"parentSpanId"`
	Name         string      `json:"name"`
	Kind         string      `json:"kind"`
	StackTrace   []string    `json:"stackTrace"`
	StartTime    dateTime    `json:"startTime"`
	EndTime      dateTime    `json:"endTime"`
	Status       status      `json:"status"`
	Attributes   attributes  `json:"attributes"`
	TimeEvents   interface{} `json:"timeEvents"`
	Links        []link      `json:"links"`
	SameProcess  bool        `json:"sameProcessAsParentSpan"`
}

type dateTime time.Time

func (dt *dateTime) UnmarshalJSON(data []byte) error {
	if dt == nil {
		return nil
	}
	pdt := &struct {
		Date         string `json:"date"`
		TimezoneType int    `json:"timezone_type"`
		Timezone     string `json:"timezone"`
	}{}
	if err := json.Unmarshal(data, pdt); err != nil {
		return err
	}
	gdt, err := time.Parse("2006-01-02 15:04:05.000000", pdt.Date)
	if err != nil {
		return err
	}
	*dt = dateTime(gdt)
	return nil
}

type status struct {
	Code    int32  `json:"code"`
	Message string `json:"message"`
}

type attributes map[string]interface{}

func (a *attributes) UnmarshalJSON(data []byte) error {
	type alias map[string]interface{}
	if a == nil {
		return nil
	}
	if bytes.Equal(data, []byte("[]")) {
		return nil
	}
	var val alias
	err := json.Unmarshal(data, &val)
	if err != nil {
		return err
	}
	*a = map[string]interface{}(val)
	return nil
}

type link struct {
	TraceID    []byte            `json:"traceId"`
	SpanID     []byte            `json:"spanId"`
	Type       string            `json:"type"`
	Attributes map[string]string `json:"attributes"`
}
