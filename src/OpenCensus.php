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

namespace OpenCensus;

use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\ExporterInterface as TraceExporter;
use OpenCensus\Stats\Stats;
use OpenCensus\Stats\Exporter\ExporterInterface as StatsExporter;

/**
 * OpenCensus configuration bootstrapping class.
 */
class OpenCensus
{
    /**
     * Initializes OpenCensus with the provided reporters.
     *
     * @param TraceExporter $traceReporter Set the reporter to use for Tracing data.
     * @param StatsExporter $statsReporter Set the handler to use for Stats data.
     * @param array $options Configuration options. See
     *        <a href="Span.html#method___construct">OpenCensus\Trace\Span::__construct()</a>
     *        for the other available options.
     *
     *      @type SamplerInterface $sampler Sampler that defines the sampling rules.
     *            **Defaults to** a new `AlwaysSampleSampler`.
     *      @type PropagatorInterface $propagator SpanContext propagator. **Defaults to**
     *            a new `HttpHeaderPropagator` instance
     *      @type array $headers Optional array of headers to use in place of $_SERVER
     */
    public static function init(
        TraceExporter $traceReporter = null,
        StatsExporter $statsReporter = null,
        array $options = []
    ) {
        if ($traceReporter !== null) {
            Tracer::start($traceReporter, $options);
        }
        if ($statsReporter !== null) {
            Stats::getInstance()->setExporter($statsReporter);
        }
    }
}
