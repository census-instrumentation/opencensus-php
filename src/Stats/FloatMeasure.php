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
 * FloatMeasure is a Measure for Float values.
 */
class FloatMeasure extends Measure
{
    use \OpenCensus\Utils\PrintableTrait;
    use MeasureHandleTrait;

    /**
     * Called by registerMeasureHandle if needed.
     *
     * @internal
     *
     * @param string $name The name of the FloatMeasure.
     * @param string $description The description of the FloatMeasure.
     * @param string $unit The unit of the FloatMeasure.
     */
    protected final function __construct(string $name, string $description, string $unit) {
        parent::__construct($name, $description, $unit);
    }

    /**
     * Constructs a new FloatMeasure.
     *
     * @param string $name Unique name of the Measure.
     * @param string $description Human readable discription of the Measure.
     * @param string $unit Unit of the Measure. See
     *     <a href="http://unitsofmeasure.org/ucum.html">Unified Code for Units of Measure</a>
     * @return FloatMeasure
     * @throws \Exception Throws on invalid measure name.
     */
    public static final function create(
        string $name, string $description = "", string $unit = Measure::DIMENSIONLESS
    ): FloatMeasure
    {
        return self::registerMeasureHandle($name, $description, $unit);
    }

    /**
     * Creates a Measurement of provided value.
     *
     * @param float $value the measurement value.
     * @return Measurement Returns a Measurement object which can be recorded.
     */
    public final function M(float $value): Measurement
    {
        return new class($this, $value) extends Measurement
        {
            /**
             * @internal
             *
             * @param Measure $measure The Measure this Measurement belongs to.
             * @param float $value The value of this Measurement.
             */

            public function __construct(Measure &$measure, float $value)
            {
                parent::__construct($measure, $value);
            }
        };
    }
}
