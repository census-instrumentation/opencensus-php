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
 * IntMeasure is a Measure for Int values.
 */
class IntMeasure extends Measure
{
    use \OpenCensus\Utils\PrintableTrait;
    use MeasureHandleTrait;

    /**
     * Called by registerMeasureHandle if needed.
     * @internal
     *
     * @param string $name The name of the IntMeasure.
     * @param string $description The description of the IntMeasure.
     * @param string $unit The unit type of the IntMeasure.
     */
    final protected function __construct(string $name, string $description, string $unit)
    {
        parent::__construct($name, $description, $unit);
    }

    /**
     * Constructs a new IntMeasure.
     *
     * @param string $name Unique name of the Measure.
     * @param string $description Human readable description of the Measure.
     * @param string $unit Unit of the Measure. See
     *     <a href="http://unitsofmeasure.org/ucum.html">Unified Code for Units of Measure</a>
     * @return IntMeasure
     * @throws \Exception Throws on invalid measure name.
     */
    final public static function create(
        string $name,
        string $description = '',
        string $unit = Measure::DIMENSIONLESS
    ): IntMeasure {
        return self::registerMeasureHandle($name, $description, $unit);
    }

    /**
     * Creates a Measurement of provided value.
     *
     * @param int $value the measurement value.
     * @return Measurement Returns a Measurement object which can be recorded.
     */
    final public function m(int $value): Measurement
    {
        return new class($this, $value) extends Measurement
        {
            /**
             * @internal
             *
             * @param Measure $measure The Measure.
             * @param int $value The Measurement value.
             */
            public function __construct(Measure $measure, int $value)
            {
                parent::__construct($measure, $value);
            }
        };
    }
}
