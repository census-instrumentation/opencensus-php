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
 * A key to a value stored in a TagContext
 *
 * Each TagValue has a string value. Values have a maximum length of 255 and
 * contain only printable ASCII characters.
 */
class TagValue
{
    use \OpenCensus\Utils\PrintableTrait;

    /** The maximum length for a tag value. */
    const MAX_LENGTH = 255;

    /** @var string TagValue payload */
    private $value;

    final private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Constructs a TagValue with the given string payload.
     *
     * The name must meet the following requirements:
     * <ol>
     *   <li>It cannot be longer than TagValue::MAX_LENGTH
     *   <li>It can only contain printable ASCII characters.
     * </ol>
     *
     * @param string $value the value payload.
     * @return TagValue
     * @throws \Exception if name is not valid.
     */
    public static function create(string $value): TagValue
    {
        if (strlen($value) > self::MAX_LENGTH || !self::isPrintable($value)) {
            throw new \Exception("Invalid TagValue: $value");
        }
        return new self($value);
    }

    /**
     * Returns the payload of the TagValue.
     *
     * @return string
     */
    final public function getValue(): string
    {
        return $this->value;
    }
}
