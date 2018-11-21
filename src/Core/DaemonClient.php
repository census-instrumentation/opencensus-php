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
use \OpenCensus\Stats\Measure;
use \OpenCensus\Stats\IntMeasure;
use \OpenCensus\Stats\FloatMeasure;
use \OpenCensus\Stats\Measurement;
use \OpenCensus\Stats\View\View;
use \OpenCensus\Stats\View\Aggregation;
use \OpenCensus\Trace\Exporter\ExporterInterface as TraceExporter;
use \OpenCensus\Stats\Exporter\ExporterInterface as StatsExporter;

/**
 * This class is a client to the OpenCensus PHP Daemon application.
 *
 * It allows to quickly move both Tracing and Stats data handling out of band
 * and provide metrics persistence outside of PHP request oriented runtime.
 */
class DaemonClient implements StatsExporter, TraceExporter
{
    use \OpenCensus\Utils\VarintTrait;

    /**
     * Default socket path to use.
     * @var $DEFAULT_SOCKET_PATH = /tmp/ocdaemon.sock
     */
    const DEFAULT_SOCKET_PATH   = '/tmp/ocdaemon.sock';
    /** Default send timeout in seconds as float. */
    const DEFAULT_MAX_SEND_TIME = 0.005;
    /** Protocol version this client supports. */
    private const PROT_VERSION = "\x01";
    /** Start of message delimiter, allowing for recovery from truncated messages */
    private const START_OF_MSG = "\x00\x00\x00\x00";

    // message types (1 - 19)
    private const MSG_PROC_INIT     = "\x01";
    private const MSG_PROC_SHUTDOWN = "\x02";
    private const MSG_REQ_INIT      = "\x03";
    private const MSG_REQ_SHUTDOWN  = "\x04";

    // trace type (20 - 39)
    private const MSG_TRACE_EXPORT = "\x14";

    // stats types (40 - ...)
    private const MSG_MEASURE_CREATE        = "\x28";
    private const MSG_VIEW_REPORTING_PERIOD = "\x29";
    private const MSG_VIEW_REGISTER         = "\x2a";
    private const MSG_VIEW_UNREGISTER       = "\x2b";
    private const MSG_STATS_RECORD          = "\x2c";

    // measurement value types
    private const MS_TYPE_INT     = "\x01";
    private const MS_TYPE_FLOAT   = "\x02";
    private const MS_TYPE_UNKNOWN = "\xff";

    /** @var DaemonClient $instance Our singleton instance of DaemonClient. */
    private static $instance;

    /** @var float $maxSendTime Holds the maximum time allowed to send data over the wire. */
    private $maxSendTime = self::DEFAULT_MAX_SEND_TIME;

    /** @var resource $sock The used unix socket for communicating with OC Daemon. */
    private $sock;

    /** @var bool $tid Set to true when zend thread safety is enabled. */
    private $tid;

    /** @var int $seqnr The sequence number of the last message sent to daemon. */
    private $seqnr = 0;

    /** @var bool $float32 set to true will signal floats being encoded in 32 bit */
    private $float32;

    private function __construct($sock, int $maxSendTime = null)
    {
        $this->sock = $sock;
        \stream_set_blocking($this->sock, false);

        if (\is_float($maxSendTime) & $maxSendTime >= 0.001) {
            $this->maxSendTime = $maxSendTime;
        }

        if (function_exists('zend_thread_id')) {
            $this->tid = true;
        }

        if (strlen(pack('E', 1.0)) === 4) {
            $this->float32 = true;
        }

        $msg = self::PROT_VERSION;
        $msg .= self::encodeString(\phpversion());
        $msg .= self::encodeString(\zend_version());
        $this->send(self::MSG_REQ_INIT, $msg);

        // on shutdown... send shutdown message to daemon
        register_shutdown_function(function() {
            $this->send(self::MSG_REQ_SHUTDOWN);
        });
    }

    /**
     * Initialize our DaemonClient for Stats and/or Trace reporting to the
     * OpenCensus PHP Daemon service.
     *
     * @param array $options Configuration options.
     *
     *     @type string $socketPath Path of the Unix socket to communicate over.
     *     @type float $maxSendTime The maximum send time for a message payload in seconds.
     * @throws \Exception Throws on the inability to communicate with the PHP Daemon.
     * @return DaemonClient Returns the DaemonClient object on successful initialization.
     */
    public static function init(array $options = []): DaemonClient
    {
        if (self::$instance instanceof DaemonClient) {
            return self::$instance;
        }

        if (array_key_exists('maxSendTime', $options) && \is_float($options['maxSendTime'])) {
            $maxSendTime = $options['maxSendTime'];
        } else{
            $maxSendTime = self::DEFAULT_MAX_SEND_TIME;
        }

        if (array_key_exists('socketPath', $options)) {
            $socketPath = $options['socketPath'];
        } else {
            $socketPath = self::DEFAULT_SOCKET_PATH;
        }

        $sock = @\pfsockopen("unix://$socketPath", -1, $errno, $errstr, 0);
        if ($sock === false) {
            throw new \Exception("$errstr [$errno]");
        }

        return self::$instance = new DaemonClient($sock, $maxSendTime);
    }

    public static function createMeasure(Measure $measure): bool
    {
        $msg = '';
        switch(true) {
            case $measure instanceof IntMeasure:
                $msg .= self::MS_TYPE_INT;
                break;
            case $measure instanceof FloatMeasure:
                $msg .= self::MS_TYPE_FLOAT;
                break;
            default:
                $msg .= self::MS_TYPE_UNKNOWN;
                break;
        }
        $msg .= self::encodeString($measure->getName());
        $msg .= self::encodeString($measure->getDescription());
        $msg .= self::encodeString($measure->getUnit());
        return self::$instance->send(self::MSG_MEASURE_CREATE, $msg);
    }

    public static function setReportingPeriod(float $interval): bool
    {
        if ($interval < 0) {
            return false;
        }
        $msg = pack('E', $interval);
        return self::$instance->send(self::MSG_VIEW_REPORTING_PERIOD, $msg);
    }

    public static function registerView(View ...$views): bool
    {

        $msg = '';
        self::encodeUnsigned($msg, count($views));
        foreach ($views as $view) {
            $msg .= self::encodeString($view->getName());
            $msg .= self::encodeString($view->getDescription());
            $tagKeys = $view->getTagKeys();
            self::encodeUnsigned($msg, count($tagKeys));
            foreach ($tagKeys as $tagKey) {
                $msg .= self::encodeString($tagKey->getName());
            }
            $measure = $view->getMeasure();
            $msg .= self::encodeString($measure->getName());
            $msg .= self::encodeString($measure->getDescription());
            $msg .= self::encodeString($measure->getUnit());
            $aggregation = $view->getAggregation();
            self::encodeUnsigned($msg, $aggregation->getType());
            if ($aggregation->getType() === Aggregation::DISTRIBUTION) {
                $bucketBoundaries = $aggregation->getBucketBoundaries();
                self::encodeUnsigned($msg, count($bucketBoundaries));
                foreach ($bucketBoundaries as $bucketBoundary) {
                    $msg .= pack('E', $bucketBoundary);
                }
            }
        }
        return self::$instance->send(self::MSG_VIEW_REGISTER, $msg);
    }

    public static function unregisterView(View ...$views): bool
    {
        $msg = '';
        self::encodeUnsigned($msg, count($views));
        foreach ($views as $view) {
            $msg .= self::encodeString($view->getName());
        }
        return self::$instance->send(self::MSG_VIEW_UNREGISTER, $msg);
    }

    public static function recordStats(TagContext $tagContext, array $attachments, Measurement ...$ms): bool
    {
        // bail out if we don't have measurements
        if (count($ms) === 0) return true;

        $msg = '';
        self::encodeUnsigned($msg, count($ms));
        foreach ($ms as $m) {
            $measure = $m->getMeasure();
            $msg .= self::encodeString($measure->getName());
            if ($measure instanceof IntMeasure) {
                $msg .= self::MS_TYPE_INT;
                self::encodeUnsigned($msg, $m->getValue());
            } else if ($measure instanceof FloatMeasure){
                $msg .= self::MS_TYPE_FLOAT;
                $msg .= pack('E', $m->getValue());
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
            $msg .= self::encodeString($value);
        }
        return self::$instance->send(self::MSG_STATS_RECORD, $msg);
    }

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

    /**
     * Send message to daemon
     *
     * Message layout:
     *   MSG_PREFIX  : 32 bit (0 value to aide in recovery from message truncation)
     *   MESSAGE_TYPE: byte
     *   SEQUENCE_NR : varint
     *   PROCESS_ID  : varint
     *   THREAD_ID   : varint (0 in most PHP deployments - (ZTS disabled))
     *   START_TIME  : float (measured in microseconds, 32 or 64 bit depending on env.)
     *   MSG_LEN     : varint (length of message payload)
     *   MSG         : encoded message of size MSG_LEN
     *
     * @param string $type The message type (1 byte).
     * @param string $msg The message payload.
     * @return bool Returns true on successful operation.
     */
    private final function send(string $type, string $msg = ''): bool
    {
        $start = microtime(true);
        $maxEnd = $start + $this->maxSendTime;

        $buf = self::START_OF_MSG . $type;
        self::encodeUnsigned($buf, ++$this->seqnr);
        self::encodeUnsigned($buf, \getmypid());
        self::encodeUnsigned($buf, $this->getmytid());
        if ($this->float32) {
            // pad with nulls on both sides (easy to detect on other size)
            $buf .= "\x00\x00" . pack('E', $start) . "\x00\x00";
        } else {
            $buf .= pack('E', $start);
        }
        self::encodeUnsigned($buf, strlen($msg));
        $buf .= $msg;

        $remaining = strlen($buf);
        while ($remaining > 0 && microtime(true) < ($maxEnd)) {
            $c = \fwrite($this->sock, $buf, $remaining);
            $remaining -= $c;
            $buf = substr($buf, $c);
        }
        return ($remaining === 0);
    }

    /**
     * Encodes message payload by prefixing the message length as unsigned varint.
     *
     * @param string $data The message payload to prefix.
     * @return string returns The unsigned varint length prefixed payload.
     */
    private static final function encodeString(string $data): string
    {
        $buf = '';
        self::encodeUnsigned($buf, strlen($data));
        return $buf . $data;
    }

    /**
     * Returns the Thread ID of this PHP request run if ZTS is enabled.
     *
     * @return int Thread id of our PHP script run.
     */
    private final function getmytid(): int
    {
        if ($this->tid === true) {
            return \zend_thread_id();
        }
        return 0;
    }
}
