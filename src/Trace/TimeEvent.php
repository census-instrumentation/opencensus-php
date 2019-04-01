<?php
/**
 * Copyright 2017 OpenCensus Authors
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

namespace OpenCensus\Trace;

/**
 * An abstract class that represents a TimeEvent resource.
 */
abstract class TimeEvent
{
    use DateFormatTrait;

    /**
     * @var \DateTimeInterface The time of this event
     */
    protected $time;

    /**
     * Create a new TimeEvent.
     *
     * @param array $options [optional] Configuration options.
     *
     *      @type \DateTimeInterface|int|float $time The time of this event.
     */
    public function __construct($options = [])
    {
        $options += [
            'time' => null
        ];
        $this->setTime($options['time']);
    }

    /**
     * Return the time of this event.
     *
     * @return \DateTimeInterface
     */
    public function time(): \DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set the time for this event.
     *
     * @param \DateTimeInterface|int|float $time The time of this event.
     */
    public function setTime($time = null): void
    {
        $this->time = $this->formatDate($time);
    }
}
