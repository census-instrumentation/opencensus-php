<?php
/**
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Trace;

/**
 * Trait which provides helper methods for converting DateTime input formats.
 */
trait DateFormatTrait
{
    /**
     * Handles parsing a \DateTimeInterface object from a provided timestamp.
     *
     * @param  \DateTimeInterface|int|float $when [optional] The end time of this span.
     *         **Defaults to** now. If provided as an int or float, it is expected to be a Unix timestamp.
     * @return \DateTimeInterface
     * @throws \InvalidArgumentException
     */
    private function formatDate($when = null)
    {
        if (!$when) {
            // now
            $when = $this->formatFloatTimeToDate(microtime(true));
        } elseif (is_numeric($when)) {
            $when = $this->formatFloatTimeToDate($when);
        } elseif (!$when instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('Invalid date format. Must be a \DateTimeInterface or numeric.');
        }
        $when->setTimezone(new \DateTimeZone('UTC'));
        return $when;
    }

    /**
     * Converts a float timestamp into a \DateTimeInterface object.
     *
     * @param float $when The Unix timestamp to be converted.
     * @return \DateTimeInterface
     */
    private function formatFloatTimeToDate($when)
    {
        return \DateTime::createFromFormat('U.u', number_format($when, 6, '.', ''));
    }
}
