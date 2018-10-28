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

/**
 * The definition of the Measurement that is taken by OpenCensus library.
 *
 * Measure represents a single numeric value to be tracked and recorded.
 * For example, latency, request bytes, and response bytes could be measures
 * to collect from a server.
 *
 * Measures by themselves have no outside effects. In order to be exported,
 * the measure needs to be used in a View. If no Views are defined over a
 * measure, there is very little cost in recording it.
 */
abstract class Measure {
    use \OpenCensus\Utils\Printable;

    protected const NAME_MAX_LENGTH = 255;
    protected const EX_INVALID_NAME = "Name should be a ASCII string with a length " .
        "no greater than " . self::NAME_MAX_LENGTH . " characters.";
    protected const EX_NAME_EXISTS = "Different Measure Type with same name already exists.";

    protected static $map = array();

    protected $name;
    protected $description;
    protected $unit;

    protected function __construct($name, $description, $unit)
    {
        $this->name = $name;
        $this->description = $description;
        $this->unit = $unit;
    }

    /**
     * Name returns the name of this measure.
     *
     * Measure names are globally unique (among all libraries linked into your
     * program).
     * We recommend prefixing the measure name with a domain name relevant to
     * your project or application.
     *
     * Measure names are never sent over the wire or exported to backends.
     * They are only used to create Views.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Discription returns the human-readable description of this measure.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Unit returns the units for the values this measure takes on.
     *
     * Units are encoded according to the case-sensitive abbreviations from the
     * Unified Code for Units of Measure: http://unitsofmeasure.org/ucum.html
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->$unit;
    }
}
