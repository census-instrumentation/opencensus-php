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

namespace OpenCensus\Utils;

/**
 * Internal utility methods for working with tag keys, tag values, and metric names.
 */
trait PrintableTrait
{
    /**
     * Determines whether the string contains only printable characters.
     *
     * @param string $str string to test.
     * @return bool returns true if string is printable.
     */
    private static function isPrintable(string $str): bool
    {
        $length = strlen($str);
        for ($i = 0; $i < $length; $i++) {
            if (!($str[$i] >= ' ' && $str[$i] <= '~')) {
                return false;
            }
        }

        return true;
    }
}
