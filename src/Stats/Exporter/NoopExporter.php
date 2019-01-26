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

class NoopExporter implements ExporterInterface
{
    public static function createMeasure(Measure $measure): bool
    {
        return true;
    }

    public static function setReportingPeriod(float $interval): bool
    {
        return true;
    }

    public static function registerView(View ...$views): bool
    {
        return true;
    }

    public static function unregisterView(View ...$views): bool
    {
        return true;
    }

    public static function recordStats(TagContext $tags, array $attachments, Measurement ...$ms): bool
    {
        return true;
    }
}
