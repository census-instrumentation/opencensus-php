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

trait AttributeTrait
{
    /**
     * @var array A set of attributes, each in the format `[KEY]:[VALUE]`.
     */
    private $attributes = [];

    /**
     * Attach attributes to this object.
     *
     * @param array $attributes Attributes in the form of $attribute => $value
     */
    public function addAttributes(array $attributes)
    {
        foreach ($attributes as $attribute => $value) {
            $this->addAttribute($attribute, $value);
        }
    }

    /**
     * Attach a single attribute to this object.
     *
     * @param string $attribute The name of the attribute.
     * @param mixed $value The value of the attribute. Will be cast to a string
     */
    public function addAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = (string) $value;
    }

    /**
     * Return the list of attributes for this object.
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }
}
