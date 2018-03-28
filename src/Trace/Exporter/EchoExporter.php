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

use OpenCensus\Trace\SpanData;

/**
 * This implementation of the ExporterInterface uses `print_r` to output
 * the trace's representation to stdout.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Exporter\EchoExporter;
 * use OpenCensus\Trace\Tracer;
 *
 * $exporter = new EchoExporter();
 * Tracer::begin($exporter);
 * ```
 */
class EchoExporter implements ExporterInterface
{
    /**
     * Report the provided Trace to a backend.
     *
     * @param  TracerInterface $tracer
     * @return bool
     */
    public function export(array $spans)
    {
        print_r($spans);
        return true;
    }
}
