<?php
/**
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Core;

use \OpenCensus\Trace\SpanData;
use \OpenCensus\Tags\TagContext;
use \OpenCensus\Stats\Measurements;
use \OpenCensus\Trace\Exporter\ExporterInterface as TraceExporter;
use \OpenCensus\Stats\Exporter\ExporterInterface as StatsExporter;

/**
 * DaemonClient is the OpenCensus client interface to the OpenCensus PHP Daemon
 * application. It allows to quickly move both Tracing and Stats data handling
 * out of band and provide metrics persistence outside of PHP request oriented
 * runtime.
 */
class DaemonClient implements StatsExporter, TraceExporter
{
    use \OpenCensus\Utils\VarintTrait;

    const DEFAULT_SOCKET_PATH = "ocdaemon.sock";
    const DEFAULT_MAX_TIME    = 0.005; // default 5 ms.

    const PROT_VERSION = "\x01"; // allows for protocol upgrading

    const START_OF_MSG = "\x00\x00\x00\x00"; // allows for recovery from truncated messages

    // message types (1 - 19)
    const MSG_PROC_INIT     = "\x01";
    const MSG_PROC_SHUTDOWN = "\x02";
    const MSG_REQ_INIT      = "\x03";
    const MSG_REQ_SHUTDOWN  = "\x04";

    // trace type (20 - 39)
    const MSG_TRACE_EXPORT = "\x14";

    // stats types (40 - ...)
    const MSG_MEASURE_CREATE        = "\x28";
    const MSG_VIEW_REPORTING_PERIOD = "\x29";
    const MSG_VIEW_REGISTER         = "\x2a";
    const MSG_VIEW_UNREGISTER       = "\x2b";
    const MSG_STATS_RECORD          = "\x2c";

    // measurement value types
    const MS_TYPE_INT     = "\x01";
    const MS_TYPE_FLOAT   = "\x02";
    const MS_TYPE_UNKNOWN = "\xff";

    private static $instance;

    /** @var float $maxSendTime maximum time allowed to send data over the wire */
    private $maxSendTime = self::DEFAULT_MAX_TIME;

    /** @var resource $sock unix socket for communicating with OC Daemon */
    private $sock = self::DEFAULT_SOCKET_PATH;

    /** @var bool $tid true if zend thread safety is enabled */
    private $tid;

    private function __construct($sock)
    {
        $this->sock = $sock;
        \stream_set_blocking($this->sock, false);

        if (function_exists('zend_thread_id')) {
            $this->tid = true;
        }

        $msg = self::PROT_VERSION;
        $msg .= self::encodeString(\phpversion());
        $msg .= self::encodeString(\zend_version());

        $this->send(self::MSG_REQ_INIT, $msg);
        register_shutdown_function([$this, 'onExit']);
    }

    public function onExit()
    {
        $this->send(self::MSG_REQ_SHUTDOWN);
    }

    public static function init(array $options = [])
    {
        if (array_key_exists('maxSendTime', $options) && \is_float($options['maxSendTime'])) {
            $maxSendTime = $options['maxSendTime'];
        }
        if (array_key_exists('socketPath', $options)) {
            $socketPath = $options['socketPath'];
        }
        if (self::$instance instanceof Daemon) {
            return self::$instance;
        }

        $sock = \pfsockopen("unix://$socketPath", -1, $errno, $errstr, 0);
        if ($sock === false) {
            throw new \Exception("$errstr [$errno]");
        }

        return self::$instance = new DaemonClient($sock);
    }

    /**
     * Record the provided Measurements, Attachments and Tags.
     *
     * @param array $ms array of Measurements.
     * @param TagContext $tagContext tags to record with our Measurements.
     * @param array $attachments key-value pairs to use for exemplar annotation.
     */
    public static function recordStats(array $ms, TagContext $tagContext, array $attachments)
    {
        // bail out if we don't have measurements
        if (count($ms) === 0) return true;

        $msg = '';
        self::encodeUnsigned($msg, count($ms));
        /** @var Measurement $ms */
        foreach ($ms as $m) {
            $measure = $m->getMeasure();
            var_dump($measure);
            $msg .= self::encodeString($measure->getName());
            if ($measure instanceof MeasureInt) {
                $msg .= self::MS_TYPE_INT;
                self::encodeUnsigned($msg, $measure->getValue());
            } else if ($measure instanceof MeasureFloat){
                $msg .= self::MS_TYPE_FLOAT;
                $msg .= pack('E', $measure->getValue());
            } else {
                $msg .= self::MS_TYPE_UNKNOWN;
            }
        }
        $tags = $tagContext->tags();
        self::encodeUnsigned($msg, count($tags));
        foreach ($tags as $tag) {
            $msg .= self::encodeString($tag->getKey()->getName());
            $msg .= self::encodeString($tag->getValue()->getValue());
        }
        self::encodeUnsigned($msg, count($attachments));
        foreach ($attachments as $key => $value) {
            $msg .= self::encodeString($key);
            $msg .= self::encodeString($attachments);
        }
        return self::$instance->send(self::MSG_STATS_RECORD, $msg);
    }

    /**
     * Export the provided SpanData
     *
     * @param SpanData[] $spans
     * @return bool
     */
    public function export(array $spans)
    {
        $spanData = json_encode(array_map(function (SpanData $span) {
            return [
                'traceId' => $span->traceId(),
                'spanId' => $span->spanId(),
                'parentSpanId' => $span->parentSpanId(),
                'name' => $span->name(),
                'kind' => $span->kind(),
                'stackTrace' => $span->stackTrace(),
                'startTime' => $span->startTime(),
                'endTime' => $span->endTime(),
                'status' => $span->status(),
                'attributes' => $span->attributes(),
                'timeEvents' => $span->timeEvents(),
                'links' => $span->links(),
                'sameProcessAsParentSpan' => $span->sameProcessAsParentSpan(),
            ];
        }, $spans));
        $len = '';
        self::encodeUnsigned($len, strlen($spanData));

        return self::$instance->send(self::MSG_TRACE_EXPORT, $len . $spanData);
    }

    private final function send(string $type, string $msg = ''): bool
    {
        $start = microtime(true);
        $maxEnd = $start + $this->maxSendTime;
        $type = self::START_OF_MSG . $type;
        self::encodeUnsigned($type, \getmypid());
        self::encodeUnsigned($type, $this->getmytid());
        $msg = $type . pack('E', $start) . $msg;

        $remaining = strlen($msg);
        while ($remaining > 0 && microtime(true) < ($start + 0.001)) {
            $c = \fwrite($this->sock, $msg, $remaining);
            $remaining -= $c;
            $msg = substr($msg, $c);
        }
        return ($remaining === 0);
    }

    private static final function encodeString(string $data): string
    {
        $buf = '';
        self::encodeUnsigned($buf, strlen($data));
        return $buf . $data;
    }

    private final function getmytid(): int
    {
        if ($this->tid === true) {
            return \zend_thread_id();
        }
        return 0;
    }
}
