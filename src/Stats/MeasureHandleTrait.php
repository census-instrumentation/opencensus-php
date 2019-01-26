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
 * Trait which handles Measure creation. Takes care of name validation and deduplication.
 */
trait MeasureHandleTrait
{
    private static function registerMeasureHandle(string $name, string $description, string $unit)
    {
        if (array_key_exists($name, parent::$map)) {
            if (!(parent::$map[$name] instanceof self)) {
                throw new \Exception(parent::EX_NAME_EXISTS);
            }
            return parent::$map[$name];
        }

        if (!self::isPrintable($name) || strlen($name) > parent::NAME_MAX_LENGTH) {
            throw new \Exception(parent::EX_INVALID_NAME);
        }

        if ($description === '') {
            $description = $name;
        }

        $m = new self($name, $description, $unit);
        Stats::getInstance()->getExporter()->createMeasure($m);
        return parent::$map[$name] = $m;
    }
}
