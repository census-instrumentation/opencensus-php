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

namespace OpenCensus\Stats;

use OpenCensus\Tags\TagContext;
use OpenCensus\Stats\Exporter\ExporterInterface;
use OpenCensus\Stats\Exporter\NoopExporter;
use OpenCensus\Stats\View\View;

/**
 * This class provides static functions to give you access to the most common Stats actions.
 * It's typically used to start the desired Stats exporter and get access to
 * Stats recording, Tag management and View registration.
 *
 * Example:
 * ```
 * use OpenCensus\Core\DaemonClient;
 * use OpenCensus\Trace\Tracer;
 * use OpenCensus\Tags\TagKey;
 * use OpenCensus\Tags\TagValue;
 * use OpenCensus\Tags\TagContext;
 * use OpenCensus\Stats\Stats;
 * use OpenCensus\Stats\Measure;
 * use OpenCensus\Stats\View\View;
 * use OpenCensus\Stats\View\Aggregation;
 *
 * try {
 *     $daemon = DaemonClient::init(array("socketPath" => "/tmp/ocdaemon.sock"));
 *     Tracer::start($daemon);
 *     Stats::setExporter($daemon);
 *     $daemon->setReportingPeriod(2.0);
 * } catch (\Exception $e) {
 *     // Unable to set Stats Exporter, proceeding as Noop
 * }
 *
 * $frontendKey = TagKey::create("example.com/keys/frontend");
 *
 * $videoSize = Measure::newIntMeasure("example.com/measure/video_size", "size of processed videos", Measure::BYTES);
 *
 * Stats::registerView(new View(
 *     "example.com/views/video_size",
 *     "processed video size over time",
 *     $videoSize,
 *     Aggregation::distribution([0, 1<<16, 1<<32]),
 *     $frontendKey
 * ));
 *
 * Tracer::inSpan(['name' => 'example.com/ProcessVideo'], function () use ($frontendKey, $videoSize) {
 *     // process video
 *     $tagCtx = TagContext::fromContext();
 *     $tagCtx->insert($frontendKey, TagValue::create("mobile-ios9.3.5"));
 *     sleep(1);
 *     Stats::newMeasurementMap()
 *         ->put($videoSize->M(25648))
 *         ->record();
 * });
 * ```
 */
class Stats
{
    /** @var Stats $instance */
    private static $instance;

    /** @var ExporterInterface $exporter */
    private $exporter;

    private function __construct()
    {
    }

    /**
      * Retrieve Stats instance.
      *
      * @return Stats
      */
    public static function getInstance(): Stats
    {
        if (self::$instance instanceof Stats) {
            return self::$instance;
        }
        self::$instance = new Stats();
        self::$instance->exporter = new NoopExporter();

        return self::$instance;
    }

    /**
     * Set the ExporterInterface to use by the Stats components.
     *
     * @param ExporterInterface $exporter
     */
    public static function setExporter(ExporterInterface $exporter): void
    {
        self::getInstance()->exporter = $exporter;
    }

    /**
     * Retrieve our ExporterInterface.
     *
     * @return ExporterInterface
     */
    public static function getExporter(): ExporterInterface
    {
        return self::getInstance()->exporter;
    }

    /**
     * Return a new TagContext object.
     *
     * @return TagContext
     */
    public static function newTagContext(): TagContext
    {
        return TagContext::new();
    }

    /**
     * Retrieve a new MeasurementInterface for recording Measurements
     *
     * @return MeasurementInterface
     */
    public static function newMeasurementMap(): MeasurementInterface
    {
        if (self::getInstance()->exporter instanceof NoopExporter) {
            return new NoopMeasurementMap();
        }
        return new MeasurementMap();
    }

    /**
     * Register one or multiple views.
     *
     * @param View ...$views the views to register.
     * @return bool
     */
    public static function registerView(View ...$views): bool
    {
        return self::getInstance()->exporter->registerView(...$views);
    }

    /**
     * Unregister one or multiple views.
     *
     * @param View ...$views the views to unregister.
     * @return bool
     */
    public static function unregisterView(View ...$views): bool
    {
        return self::getInstance()->exporter->unregisterView(...$views);
    }
}
