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

use OpenCensus\Stats\Measure;
use OpenCensus\Stats\View\Aggregation;
use OpenCensus\Stats\TagKey;

class View
{
    use \OpenCensus\Utils\PrintableTrait;

    // *var array $views map of views to make sure view names are unique.
    private static $views = array();

    private $name;
    private $description;
    private $tagKeys = array();
    private $measure;
    private $aggregation;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTagKeys(): array
    {
        return $this->tagKeys;
    }

    public function getMeasure(): Measure
    {
        return $this->measure;
    }

    public function getAggregation(): Aggregation
    {
        return $this->aggregation;
    }
}
