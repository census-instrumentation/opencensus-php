package local

import (
	"sync"

	"go.opencensus.io/stats"
	"go.opencensus.io/stats/view"
	"go.opencensus.io/tag"
)

var (
	tagMsgType, _ = tag.NewKey("msg_type")

	defaultSizeDistribution    = view.Distribution(0, 1024, 2048, 4096, 16384, 65536, 262144, 1048576, 4194304, 16777216, 67108864, 268435456, 1073741824, 4294967296)
	defaultLatencyDistribution = view.Distribution(0, 1, 2, 3, 4, 5, 6, 8, 10, 13, 16, 20, 25, 30, 40, 50, 65, 80, 100, 130, 160, 200, 250, 300, 400, 500, 650, 800, 1000, 2000, 5000, 10000, 20000, 50000, 100000)

	procLatency  = stats.Float64("opencensus.io/php_daemon/latency_queue", "Daemon Processing Queue latency", stats.UnitMilliseconds)
	msgLatency   = stats.Float64("opencensus.io/php_daemon/latency", "PHP creation to daemon processed latency", stats.UnitMilliseconds)
	msgReqCount  = stats.Int64("opencensus.io/php_daemon/request_count", "Number of received messages", stats.UnitDimensionless)
	msgProcCount = stats.Int64("opencensus.io/php_daemon/process_count", "Number of processed messages", stats.UnitDimensionless)
	msgDropCount = stats.Int64("opencensus.io/php_daemon/drop_count", "Number of dropped messages", stats.UnitDimensionless)
	msgSize      = stats.Int64("opencensus.io/php_daemon/message_size", "Size of messages", stats.UnitBytes)

	viewProcLatency = &view.View{
		Name:        "opencensus.io/php_daemon/latency_queue",
		Measure:     procLatency,
		Description: "The distribution of processing queue latencies",
		TagKeys:     []tag.Key{tagMsgType},
		Aggregation: defaultLatencyDistribution,
	}
	viewLatency = &view.View{
		Name:        "opencensus.io/php_daemon/latency",
		Measure:     msgLatency,
		Description: "The distribution of end to end latencies",
		TagKeys:     []tag.Key{tagMsgType},
		Aggregation: defaultLatencyDistribution,
	}
	viewReqCount = &view.View{
		Name:        "opencensus.io/php_daemon/requests_received",
		Measure:     msgReqCount,
		Description: "The number of received requests",
		TagKeys:     []tag.Key{tagMsgType},
		Aggregation: view.Count(),
	}
	viewProcCount = &view.View{
		Name:        "opencensus.io/php_daemon/requests_processed",
		Measure:     msgProcCount,
		Description: "The number of processed requests",
		TagKeys:     []tag.Key{tagMsgType},
		Aggregation: view.Count(),
	}
	viewDropCount = &view.View{
		Name:        "opencensus.io/php_daemon/requests_dropped",
		Measure:     msgDropCount,
		Description: "The number of dropped requests",
		TagKeys:     []tag.Key{tagMsgType},
		Aggregation: view.Count(),
	}
	viewMsgSize = &view.View{
		Name:        "opencensus.io/php_daemon/request_size",
		Measure:     msgSize,
		Description: "Size distribution of received messages",
		TagKeys:     []tag.Key{tagMsgType},
		Aggregation: defaultSizeDistribution,
	}

	registerViews sync.Once
)
