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

namespace OpenCensus\Stats\Exporter;

use \OpenCensus\Tags\TagContext;
use \OpenCensus\Stats\Measure;
use \OpenCensus\Stats\Measurement;
use \OpenCensus\Stats\View\View;

/**
 * The ExporterInterface allows you to swap out the Stats reporting mechanism.
 */
interface ExporterInterface
{
    /**
     * Register a new Measure.
     *
     * @param Measure $measure The measure to register.
     * @return bool Returns true on successful send operation.
     */
    public static function createMeasure(Measure $measure): bool;

    /**
     * Adjust the stats reporting period of the Daemom.
     *
     * @param float $interval Reporting interval of the daemon in seconds.
     * @return bool Returns true on successful send operation.
     */
    public static function setReportingPeriod(float $interval): bool;

    /**
     * Register views.
     *
     * @param View[] ...$views One or more Views to register.
     * @return bool Returns true on successful send operation.
     */
    public static function registerView(View ...$views): bool;

    /**
     * Unregister views.
     *
     * @param View[] ...$views Views to unregister.
     * @return bool Returns true on successful send operation.
     */
    public static function unregisterView(View ...$views): bool;

    /**
     * Record the provided Measurements, Attachments and Tags.
     *
     * @param TagContext $tagContext TagContext to record with our Measurements.
     * @param array $attachments Key-value pairs to use for exemplar annotation.
     * @param Measurement[] ...$ms One or more measurements to record.
     * @return bool Returns true on successful send operation.
     */
    public static function recordStats(TagContext $tagContext, array $attachments, Measurement ...$ms): bool;
}
