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

use OpenCensus\Core\Context;
use OpenCensus\Tags\TagContext;
use OpenCensus\Stats\MeasurementMap;
use OpenCensus\Stats\NoopMeasurementMap;
use OpenCensus\Stats\Stats;
use OpenCensus\Stats\Measurement;
use OpenCensus\Stats\MeasurementInterface;
use OpenCensus\Stats\Exporter\ExporterInterface;
use OpenCensus\Stats\Exporter\NoopExporter;
use OpenCensus\Stats\Views\ViewManager;

class Stats
{
    /** @var Stats $instance */
    private static $instance;

    /** @var ExporterInterface $exporter */
    private $exporter;

    private function __construct() {}

    /**
      * Retrieve Stats instance.
      *
      * @return Stats
      */
    public static function getInstance(): Stats
    {
        if (self::$instance instanceof Stats)
        {
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
    public static function setExporter(ExporterInterface $exporter)
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
        return new TagContext();
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
     * @param View[] ...$views the views to register.
     * @return bool
     */
    public static function registerView(View ...$views): bool
    {
        return self::getInstance()->exporter->registerView($views);
    }

    /**
     * Unregister one or multiple views.
     *
     * @param View[] ...$views the views to unregister.
     * @return bool
     */
    public static function unregisterView(View ...$views): bool
    {
        return self::getInstance()->exporter->unregisterView($views);
    }

}
