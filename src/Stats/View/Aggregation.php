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
    const NONE         = 0;
    const COUNT        = 1;
    const SUM          = 2;
    const DISTRIBUTION = 3;
    const LAST_VALUE   = 4;

    /** @var int $type Aggragation type */
    var $type = 0;

    /**
     * @var float[] $bounds Bucket boundaries if this Aggregation represents a
     * distribution, see Distribution.
     */
     var $bounds;

     private final function __construct(int $type, array $bounds = null)
     {
         $this->type = $type;
         $this->bounds = $bounds;
     }

     /**
      * Returns the type of the aggregation.
      *
      * @return int The Aggregation Type.
      */
     public final function getType(): int
     {
         return $this->type;
     }

     /**
      * Returns the bucket boundaries of the Distribution Aggregation.
      *
      * @return float[] Returns the bucket boundaries.
      */
     public final function getBucketBoundaries(): array
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
     public static final function count(): Aggregation
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
     public static final function distribution(array $bounds): Aggregation
     {
         foreach ($bounds as &$value) {
             if (!is_float($value) && !is_integer($value)) {
                 throw new \Exception("provided bucket boundaries need to be of type float");
             }
             $value = (float) $value;
         }
         sort($bounds);

         return new self(self::DISTRIBUTION, $bounds);
     }

     /**
      * Returns a new LastValue Aggregation.
      *
      * @return Aggregation
      */
     public static final function lastValue(): Aggregation
     {
         return new self(self::LAST_VALUE);
     }

     /**
      * Returns a new Sum Aggregation.
      *
      * @return Aggregation
      */
     public static final function sum(): Aggregation
     {
         return new self(self::SUM);
     }
}
