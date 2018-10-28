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

class TagMap
{
    /**
     * The maximum length for a serialized TagMap.
     */
    const MAX_LENGTH = 8192;
    
    /**
     * Invalid Context Error message
     */
    const EX_INVALID_CONTEXT = "Serialized context should be a string with a length no greater than " .
        self::MAX_LENGTH . " characters.";

    /**
     * @var array $m TagMap content.
     */
    private $m = array();

    /**
     * Returns the value for the key if a value for the key exists. If it does
     * not exist, false will be returned.
     *
     * @param TagKey $k The key to retrieve the value for.
     * @return TagValue|false
     */
    public final function value(TagKey $k)
    {
        if (!in_array($k->getName(), $m)) {
            return false;
        }
        return $m[$k->getName()];
    }

    /**
     * Serializes the TagMap to a string representation.
     *
     * @return string
     * @throws \Exception on failure to serialize.
     */
    public final function toString()
    {
        ksort($this->m);
        $buf = '{ ';
        foreach ($this->m as $key => &$value) {
            $buf .= '{'.$key.' '.$value.'}';
        }
        $buf .= ' }';
        if (strlen($buf) > MAX_LENGTH) {
            throw new \Exception(EX_INVALID_CONTEXT);
        }
    }
}
