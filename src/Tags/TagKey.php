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
 * Each TagKey has a string name. Names have a maximum length of 255 and contain
 * only printable ASCII characters.
 */
class TagKey
{
    use \OpenCensus\Utils\PrintableTrait;

    /** The maximum length for a tag key name. */
    const MAX_LENGTH = 255;

    /** @var string TagKey name */
    private $name;

    private final function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Constructs a TagKey with the given name.
     *
     * The name must meet the following requirements:
     * <ol>
     *   <li>It cannot be empty
     *   <li>It cannot be longer than TagKey::MAX_LENGTH
     *   <li>It can only contain printable ASCII characters.
     * </ol>
     *
     * @param string $name the name of the key
     * @return TagKey
     * @throws \Exception if name is not valid.
     */
    public static function create(string $name): TagKey
    {
        if (
            strlen($name) == 0 || strlen($name) > self::MAX_LENGTH ||
            !self::isPrintable($name)
        ) {
            throw new \Exception("Invalid TagKey name: ". $name);
        }
        return new self($name);
    }

    /**
     * Returns the name of the TagKey.
     *
     * @return string
     */
    public final function getName(): string
    {
        return $this->name;
    }
}
