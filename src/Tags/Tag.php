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

namespace OpenCensus\Tags;

/**
 * Tag is a key value pair that can be propagated on the wire.
 */
class Tag
{
    /**
     * @var TagKey
     */
    private $key;

    /**
     * @var TagValue
     */
    private $value;

    /**
     * Create Tag from the given key and value.
     *
     * @param TagKey $key
     * @param TagValue $value
     */
    public function __contruct(TagKey $key, TagValue $value)
    {
        $this->key = $key;
        $this->value = $balue;
    }

    /**
     * Returns the Tag's key.
     *
     * @return TagKey
     */
    public final function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the Tag's value.
     *
     * @return TagValue
     */
    public final function getValue()
    {
        return $this->value;
    }
}
