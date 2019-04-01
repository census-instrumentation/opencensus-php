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

/**
 * Aggregation represents a data aggregation method.
 * Use one of the static  methods count, sum, lastValue or distribution to
 * construct an Aggregation.
 */
class Aggregation
{
    /** No or unknown Aggregation type. */
    public const NONE         = 0;
    /** Count Aggregation type. */
    public const COUNT        = 1;
    /** Sum Aggregation type. */
    public const SUM          = 2;
    /** Distribution Aggregation type. */
    public const DISTRIBUTION = 3;
    /** LastValue Aggregation type. */
    public const LAST_VALUE   = 4;

    /** @var int $type Aggregation type */
    private $type;

    /**
     * @var float[] $bounds Bucket boundaries if this Aggregation represents a
     * distribution, see Distribution.
     */
    private $bounds;

    final private function __construct(int $type, array $bounds = null)
    {
        $this->type = $type;
        $this->bounds = $bounds;
    }

     /**
      * Returns the type of the aggregation.
      *
      * @return int The Aggregation Type.
      */
    final public function getType(): int
    {
        return $this->type;
    }

     /**
      * Returns the bucket boundaries of the Distribution Aggregation.
      *
      * @return float[] Returns the bucket boundaries.
      */
    final public function getBucketBoundaries(): array
    {
        if ($this->type !== self::DISTRIBUTION) {
            return array();
        }
        return $this->bounds;
    }

     /**
      * Returns a new Count Aggregation
      *
      * @return Aggregation
      */
    final public static function count(): Aggregation
    {
        return new self(self::COUNT);
    }

     /**
      * Returns a new Distribution Aggregation with the provided Bucket Boundaries.
      * Bucket Boundaries needs to be an array of integer and/or floats.
      *
      * @param float[] $bounds The bucket boundaries for distribution aggregation.
      * @throws \Exception Throws on invalid bucket boundaries.
      * @return Aggregation
      */
    final public static function distribution(array $bounds): Aggregation
    {
        foreach ($bounds as $index => $value) {
            if (!is_float($value) && !is_int($value)) {
                throw new \RuntimeException('Provided bucket boundaries need to be of type float.');
            }
            $bounds[$index] = (float) $value;
        }
        sort($bounds);

        return new self(self::DISTRIBUTION, $bounds);
    }

     /**
      * Returns a new LastValue Aggregation.
      *
      * @return Aggregation
      */
    final public static function lastValue(): Aggregation
    {
        return new self(self::LAST_VALUE);
    }

     /**
      * Returns a new Sum Aggregation.
      *
      * @return Aggregation
      */
    final public static function sum(): Aggregation
    {
        return new self(self::SUM);
    }
}
