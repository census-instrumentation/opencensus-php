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

require __DIR__.'/../../vendor/autoload.php';

/**
 * helloworld is an example program that collects data for video size.
 */

use OpenCensus\Core\DaemonClient;
use OpenCensus\Stats\Stats;
use OpenCensus\Stats\Measure;
use OpenCensus\Stats\View\View;
use OpenCensus\Stats\View\Aggregation;
use OpenCensus\Trace\Tracer;
use OpenCensus\Tags\TagContext;
use OpenCensus\Tags\TagKey;
use OpenCensus\Tags\TagValue;

try {
    // initialize and register a DaemonClient for exporting stats and traces.
    $daemon = DaemonClient::init();
    Stats::setExporter($daemon);
    // start our main request trace.
    Tracer::start($daemon);
    // let's add some environment details to our root span.
    Tracer::addAttribute("PHP", phpversion());
    Tracer::addAttribute("Zend", zend_version());
} catch (\Exception $e) {
    echo "warning: metrics and tracing disabled: " . $e->getMessage() . PHP_EOL;
}

// $frontendKey allows us to break down the recorded data by the frontend used
// when uploading the video.
$frontendKey = TagKey::create("example.com/keys/frontend");

// $videoSize will measure the size of processed videos.
$videoSize = Measure::newIntMeasure("example.com/measure/video_size", "size of processed videos", Measure::BYTES);

try {
    // Create and register a view to see the processed video size distribution
    // broken down by frontend.
    Stats::registerView(new View(
        "example.com/views/video_size",
        "processed video size over time",
        $videoSize,
        Aggregation::distribution([0, 1<<16, 1<<32]),
        $frontendKey
    ));
} catch (\Exception $e) {
    echo "warning: unable to register view: " . $e->getMessage() . PHP_EOL;
}

// fake some frontend type.
switch (random_int(1, 5)) {
    case 1: $deviceType = 'ios';     break;
    case 2: $deviceType = 'android'; break;
    case 3: $deviceType = 'windows'; break;
    case 4: $deviceType = 'osx';     break;
    case 5: $deviceType = 'linux';   break;
}

// process the video and instrument "the processing" by creating a span and
// collecting metrics about the operation.
Tracer::inSpan(
    ['name' => 'example.com/ProcessVideo'],
    function () use ($frontendKey, $videoSize, $deviceType)
    {
        $frontendValue = TagValue::create($deviceType);

        // tag device type.
        $tagCtx = TagContext::new();
        $tagCtx->insert($frontendKey, $frontendValue);
        $ctx = $tagCtx->newContext();
        $ctx->attach();

        // fake a processed video size.
        $processedVideoSize = random_int(1<<20, 1<<30);

        echo "{$deviceType} requested processing of video [size: {$processedVideoSize}]" . PHP_EOL;

        // fake a processing time.
        time_nanosleep($processedVideoSize/1e8,$processedVideoSize/10);

        // add some additional tracing details.
        Tracer::addAttribute("frontend"  , $frontendValue->getValue());
        Tracer::addAttribute("video-size", $processedVideoSize);

        // record our stats and attach our span identifiers.
        Stats::newMeasurementMap()
            ->put($videoSize->m($processedVideoSize))
            ->putAttachment('traceId', $ctx->value('traceId'))
            ->putAttachment('spanId' , $ctx->value('spanId'))
            ->record();
    }
);
