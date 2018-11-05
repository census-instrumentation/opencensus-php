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

class IntMeasure extends Measure
{
    use \OpenCensus\Utils\PrintableTrait;
    use MeasureHandleTrait;

    protected final function __construct(string $name, string $description, string $unit) {
        parent::__construct($name, $description, $unit);
    }

    /**
     * Constructs a new IntMeasure
     *
     * @param string $name
     * @param string $description
     * @param string $unit
     * @return IntMeasure
     * @throws \Exception on invalid measure name.
     */
    public static final function create(
        string $name, string $description = "", string $unit = Units::Dimensionless
    ): IntMeasure
    {
        return self::registerMeasureHandle($name, $description, $unit);
    }

    public final function M(int $value): Measurement
    {
        return new class($this, $value) extends Measurement
        {
            public function __construct(Measure &$measure, int $value)
            {
                parent::__construct($measure, $value);
            }
        };
    }
}
