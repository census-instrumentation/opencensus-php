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
 * A class that represents an Annotation resource.
 */
class Annotation extends TimeEvent
{
    use AttributeTrait;

    /**
     * @var string A user-supplied message describing the event.
     */
    private $description;

    /**
     * Create a new Annotation.
     *
     * @param string $description A user-supplied message describing the event.
     * @param array $options [optional] Configuration options.
     *
     *      @type array $attributes Attributes for this annotation.
     *      @type \DateTimeInterface|int|float $time The time of this event.
     */
    public function __construct(string $description, array $options = [])
    {
        parent::__construct($options);
        $this->description = $description;
        if (array_key_exists('attributes', $options)) {
            $this->addAttributes($options['attributes']);
        }
    }

    /**
     * Return the description of this annotation.
     *
     * @return string
     */
    public function description(): string
    {
        return $this->description;
    }
}
