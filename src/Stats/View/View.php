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

namespace OpenCensus\Stats\View;

use OpenCensus\Tags\TagKey;
use OpenCensus\Stats\Measure;

/**
 * View allows users to aggregate the recorded stats.Measurements.
 * Views need to be passed to the Stats::registerView function before data will
 * be collected and sent to Exporters.
 */
class View
{
    use \OpenCensus\Utils\PrintableTrait;

    /** @var array $views map of views to make sure view names are unique. */
    private static $views = array();

    /** @var string $name */
    private $name;
    /** @var string $description */
    private $description;
    /** @var TagKey[] $tagKeys */
    private $tagKeys = array();
    /** @var Measure $measure */
    private $measure;
    /** @var Aggregation $aggregation */
    private $aggregation;

    /**
     * Creates a new View.
     *
     * @param string $name The unique name of the View.
     * @param string $description The human readable description of the view.
     * @param Measure $measure The measure this View aggregates.
     * @param Aggregation $aggregation The Aggregation used for this view.
     * @param TagKey ...$tagKeys The TagKeys that describe the grouping of this view.
     */
    public final function __construct(
        string $name, string $description, Measure $measure,
        Aggregation &$aggregation, TagKey ...$tagKeys
    )
    {
        if ($name === '') {
            $name = $measure->getName();
        }

        if ($description === '') {
            $description = $measure->getDescription();
        }
        ksort($tagKeys);

        $this->name        = $name;
        $this->description = $description;
        $this->tagKeys     = $tagKeys;
        $this->measure     = $measure;
        $this->aggregation = $aggregation;
    }

    /**
     * Retrieves the name of the view.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the description of the view.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Retrieves the TagKeys used by the view.
     *
     * @return TagKey[] Array of TagKey.
     */
    public function getTagKeys(): array
    {
        return $this->tagKeys;
    }

    /**
     * Return the Measure this View aggregates.
     *
     * @return Measure
     */
    public function getMeasure(): Measure
    {
        return $this->measure;
    }

    /**
     * Returns the Aggregation of the View.
     *
     * @return Aggregation
     */
    public function getAggregation(): Aggregation
    {
        return $this->aggregation;
    }
}
