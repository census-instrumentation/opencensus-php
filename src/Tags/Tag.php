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
 * A tag is a key-value pair.
 *
 * Tags are values propagated through the Context subsystem inside a process
 * and among processes by any transport (e.g RPC, HTTP, etc.). For example tags
 * are used by the Stats component to break down measurements by arbitrary
 * metadata set in the current process or propagated from a remote caller.
 */
class Tag
{
    /** @var TagKey $key */
    private $key;

    /** @var TagValue $value */
    private $value;

    /**
     * Creates a Tag out of a TagKey and TagValue.
     *
     * @param TagKey $key The TagKey.
     * @param TagValue $value The TagValue.
     * @return Tag
     */
    public final function __construct(TagKey $key, TagValue $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
      * Returns the TagKey.
      *
      * @return TagKey
      */
    public final function getKey(): TagKey
    {
        return $this->key;
    }

    /**
      * Returns the TagValue.
      *
      * @return TagValue
      */
    public final function getValue(): TagValue
    {
        return $this->value;
    }
}
