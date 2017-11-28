<?php
/**
 * Copyright 2017 OpenCensus Authors
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

namespace OpenCensus\Trace\Exporter;

use Google\Cloud\Core\Batch\BatchRunner;
use Google\Cloud\Core\Batch\BatchTrait;
use Google\Cloud\Trace\TraceClient;
use Google\Cloud\Trace\Span;
use Google\Cloud\Trace\Trace;
use OpenCensus\Trace\Tracer\TracerInterface;

/**
 * This implementation of the ExporterInterface use the BatchRunner to provide
 * reporting of Traces and their Spans to Google Cloud Stackdriver Trace.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Tracer;
 * use OpenCensus\Trace\Exporter\StackdriverExporter;
 *
 * $reporter = new StackdriverExporter([
 *   'clientConfig' => [
 *      'projectId' => 'my-project'
 *   ]
 * ]);
 * Tracer::start($reporter);
 * ```
 *
 * The above configuration will synchronously report the traces to Google Cloud
 * Stackdriver Trace. You can enable an experimental asynchronous reporting
 * mechanism using
 * <a href="https://github.com/GoogleCloudPlatform/google-cloud-php/tree/master/src/Core/Batch">BatchDaemon</a>.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Tracer;
 * use OpenCensus\Trace\Exporter\StackdriverExporter;
 *
 * $reporter = new StackdriverExporter([
 *   'async' => true,
 *   'clientConfig' => [
 *      'projectId' => 'my-project'
 *   ]
 * ]);
 * Tracer::start($reporter);
 * ```
 *
 * Note that to use the `async` option, you will also need to set the
 * `IS_BATCH_DAEMON_RUNNING` environment variable to `true`.
 *
 * @experimental The experimental flag means that while we believe this method
 *      or class is ready for use, it may change before release in backwards-
 *      incompatible ways. Please use with caution, and test thoroughly when
 *      upgrading.
 */
class StackdriverExporter implements ExporterInterface
{
    const VERSION = '0.1.0';

    // These are Stackdriver Trace's common attributes
    const AGENT = '/agent';
    const COMPONENT = '/component';
    const ERROR_MESSAGE = '/error/message';
    const ERROR_NAME = '/error/name';
    const HTTP_CLIENT_CITY = '/http/client_city';
    const HTTP_CLIENT_COUNTRY = '/http/client_country';
    const HTTP_CLIENT_PROTOCOL = '/http/client_protocol';
    const HTTP_CLIENT_REGION = '/http/client_region';
    const HTTP_HOST = '/http/host';
    const HTTP_METHOD = '/http/method';
    const HTTP_REDIRECTED_URL = '/http/redirected_url';
    const HTTP_STATUS_CODE = '/http/status_code';
    const HTTP_URL = '/http/url';
    const HTTP_USER_AGENT = '/http/user_agent';
    const PID = '/pid';
    const TID = '/tid';

    const GAE_APPLICATION_ERROR = 'g.co/gae/application_error';
    const GAE_APP_MODULE = 'g.co/gae/app/module';
    const GAE_APP_MODULE_VERSION = 'g.co/gae/app/module_version';
    const GAE_APP_VERSION = 'g.co/gae/app/version';

    use BatchTrait;

    /**
     * @var TraceClient
     */
    private static $client;

    /**
     * @var bool
     */
    private $async;

    /**
     * Create a TraceExporter that utilizes background batching.
     *
     * @param array $options [optional] Configuration options.
     *
     *     @type TraceClient $client A trace client used to instantiate traces
     *           to be delivered to the batch queue.
     *     @type bool $debugOutput Whether or not to output debug information.
     *           Please note debug output currently only applies in CLI based
     *           applications. **Defaults to** `false`.
     *     @type array $batchOptions A set of options for a BatchJob. See
     *           <a href="https://github.com/GoogleCloudPlatform/google-cloud-php/blob/master/src/Core/Batch/BatchJob.php">\Google\Cloud\Core\Batch\BatchJob::__construct()</a>
     *           for more details.
     *           **Defaults to** ['batchSize' => 1000,
     *                            'callPeriod' => 2.0,
     *                            'workerNum' => 2].
     *     @type array $clientConfig Configuration options for the Trace client
     *           used to handle processing of batch items.
     *           For valid options please see
     *           <a href="https://github.com/GoogleCloudPlatform/google-cloud-php/blob/master/src/Trace/TraceClient.php">\Google\Cloud\Trace\TraceClient::__construct()</a>.
     *     @type BatchRunner $batchRunner A BatchRunner object. Mainly used for
     *           the tests to inject a mock. **Defaults to** a newly created
     *           BatchRunner.
     *     @type string $identifier An identifier for the batch job.
     *           **Defaults to** `stackdriver-trace`.
     *     @type bool $async Whether we should try to use the batch runner.
     *           **Defaults to** `false`.
     */
    public function __construct(array $options = [])
    {
        $this->async = isset($options['async']) ? $options['async'] : false;
        $this->setCommonBatchProperties($options + [
            'identifier' => 'stackdriver-trace',
            'batchMethod' => 'insertBatch'
        ]);
        self::$client = isset($options['client'])
            ? $options['client']
            : new TraceClient($this->clientConfig);
    }

    /**
     * Report the provided Trace to a backend.
     *
     * @param  TracerInterface $tracer
     * @return bool
     */
    public function report(TracerInterface $tracer)
    {
        $this->processSpans($tracer);
        $trace = self::$client->trace(
            $tracer->spanContext()->traceId()
        );
        $spans = $this->convertSpans($tracer, $trace);

        if (empty($spans)) {
            return false;
        }

        // build a Trace object and assign Spans
        $trace->setSpans($spans);

        try {
            if ($this->async) {
                return $this->batchRunner->submitItem($this->identifier, $trace);
            } else {
                return self::$client->insert($trace);
            }
        } catch (\Exception $e) {
            error_log('Reporting the Trace data failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Perform any pre-conversion modification to the spans
     *
     * @param TracerInterface $tracer
     * @param array $headers [optional] Array of headers to read from instead of $_SERVER
     */
    public function processSpans(TracerInterface $tracer, $headers = null)
    {
        // detect common attributes
        $this->addCommonAttributes($tracer, $headers);
    }

    /**
     * Convert spans into Zipkin's expected JSON output format.
     *
     * @param TracerInterface $tracer
     * @param Trace $trace
     * @return array Representation of the collected trace spans ready for serialization
     */
    public function convertSpans(TracerInterface $tracer)
    {
        $traceId = $tracer->spanContext()->traceId();

        // transform OpenCensus Spans to Google\Cloud\Trace\Spans
        return array_map(function ($span) use ($traceId) {
            return new Span($traceId, [
                'name' => $span->name(),
                'startTime' => $span->startTime(),
                'endTime' => $span->endTime(),
                'spanId' => $span->spanId(),
                'parentSpanId' => $span->parentSpanId(),
                'attributes' => $span->attributes(),
                'stackTrace' => $span->stackTrace()
            ]);
        }, $tracer->spans());
    }

    /**
     * Returns an array representation of a callback which will be used to write
     * batch items.
     *
     * @return array
     */
    protected function getCallback()
    {
        if (!isset(self::$client)) {
            self::$client = new TraceClient($this->clientConfig);
        }

        return [self::$client, $this->batchMethod];
    }

    private function addCommonAttributes(&$tracer, $headers = null)
    {
        $headers = $headers ?: $_SERVER;

        $attributeMap = [
            self::HTTP_URL => ['REQUEST_URI'],
            self::HTTP_METHOD => ['REQUEST_METHOD'],
            self::HTTP_CLIENT_PROTOCOL => ['SERVER_PROTOCOL'],
            self::HTTP_USER_AGENT => ['HTTP_USER_AGENT'],
            self::HTTP_HOST => ['HTTP_HOST', 'SERVER_NAME'],
            self::GAE_APP_MODULE => ['GAE_SERVICE'],
            self::GAE_APP_MODULE_VERSION => ['GAE_VERSION'],
            self::HTTP_CLIENT_CITY => ['HTTP_X_APPENGINE_CITY'],
            self::HTTP_CLIENT_REGION => ['HTTP_X_APPENGINE_REGION'],
            self::HTTP_CLIENT_COUNTRY => ['HTTP_X_APPENGINE_COUNTRY']
        ];
        foreach ($attributeMap as $attributeKey => $headerKeys) {
            if ($val = $this->detectKey($headerKeys, $headers)) {
                $tracer->addRootAttribute($attributeKey, $val);
            }
        }
        $tracer->addRootAttribute(self::PID, '' . getmypid());
        $tracer->addRootAttribute(self::AGENT, 'opencensus-php ' . self::VERSION);
    }

    private function detectKey(array $keys, array $array)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                return $array[$key];
            }
        }
        return null;
    }
}
