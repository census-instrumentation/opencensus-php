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
use \OpenCensus\Stats\Measurements;

/**
 * The ExporterInterface allows you to swap out the Stats reporting mechanism
 */
interface ExporterInterface
{
    /**
     * Initialize our Exporter implementation
     *
     * @param array $options exporter options
     * @return ExporterInterface
     */
    public static function init(array $options = []);

    /**
     * Record Measurements together with TagContext and Exemplars
     *
     * @param Measurement[] $ms measurements to record.
     * @param TagContext $tags explicit TagContext items to upsert into Tags found in Context.
     * @param array $attachments key value pairs to annotate for Examplar.
     * @return bool on successful export returns true
     */
    public static function recordStats(array $ms, TagContext $tags, array $attachments);
}
