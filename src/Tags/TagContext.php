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

use OpenCensus\Core\Context;

/**
 * TagContext represents a collection of tags.
 * A TagContext can be used to label anything that is associated with a specific
 * operation, such as an HTTP request. Each tag is composed of a key (TagKey),
 * and a value (TagValue).
 * A TagContext represents a map from keys to values, i.e., each key is
 * associated with exactly one value, but multiple keys can be associated with
 * the same value. TagContext is serializable, and it represents all of the
 * information that must be propagated across process boundaries.
 */
class TagContext
{
    use \OpenCensus\Utils\PrintableTrait;

    /** The key used for storing and retrieving a TagContext object from Context. */
    private const CTX_KEY = '__OCTagContext__';

    /** The maximum length for a serialized TagContext. */
    const MAX_LENGTH = 8192;

    /** Invalid Context Error message */
    const EX_INVALID_CONTEXT = "Serialized context should be a string " .
        "with a length no greater than " . self::MAX_LENGTH . " characters.";

    /**
     * @var Tag[] $m TagContext content.
     */
    private $m = array();

    private function __construct()
    {
    }

    /**
     * Returns the value for the key if a value for the key exists.
     *
     * @param TagKey $key The key to retrieve the value for.
     * @return string
     */
    final public function value(TagKey $key): string
    {
        return $this->m[$key->getName()]->getValue();
    }

    /**
     * Insert a Tag into TagContext given provided TagKey and string value.
     * If TagKey already exists in TagContext this method is a noop.
     *
     * @param TagKey $key the key of the Tag.
     * @param TagValue $value the value of the Tag.
     * @return bool returns true on successful insert.
     */
    final public function insert(TagKey $key, TagValue $value): bool
    {
        if (\array_key_exists($key->getName(), $this->m)) {
            return false;
        }

        $this->m[$key->getName()] = new Tag($key, $value);
        return true;
    }

    /**
     * Update a Tag into TagContext given provided TagKey with string value.
     * If TagKey does not exist in TagContext this method is a noop.
     *
     * @param TagKey $key the key of the Tag.
     * @param TagValue $value the value of the Tag.
     * @return bool returns true on successful update.
     */
    final public function update(TagKey $key, TagValue $value): bool
    {
        if (!\array_key_exists($key->getName(), $this->m)) {
            return false;
        }

        $this->m[$key->getName()] = new Tag($key, $value);
        return true;
    }

    /**
     * Insert or Update a Tag into TagContext given provided TagKey with
     * provided string value.
     *
     * @param TagKey $key key of the Tag.
     * @param TagValue $value value of the Tag.
     */
    final public function upsert(TagKey $key, TagValue $value)
    {
        $this->m[$key->getName()] = new Tag($key, $value);
    }

    /**
     * Deletes Tag from TagContext identified by its TagKey.
     *
     * @param TagKey $key
     * @return bool returns true if Tag was found and deleted.
     */
    final public function delete(TagKey $key): bool
    {
        if (!\array_key_exists($key->getName(), $this->m)) {
            return false;
        }
        unset($this->m[$key->getName()]);
        return true;
    }

    /**
     * Serializes the TagContext to a string representation.
     *
     * @return string
     * @throws \Exception on failure to serialize.
     */
    final public function __toString(): string
    {
        ksort($this->m);
        $buf = '{ ';
        foreach ($this->m as $key => &$tag) {
            $buf .= '{' . $key . ' ' . $tag->getValue()->getValue() . '}';
        }
        $buf .= ' }';
        return $buf;
    }

    /**
     * Returns an array of Tag objects this map holds.
     *
     * @return Tag[]
     */
    final public function tags(): array
    {
        return array_values($this->m);
    }

    /**
     * Returns an empty YagContext object.
     *
     * @return TagContext
     */
    final public static function empty(): TagContext
    {
        return new self();
    }
    /**
     * Extract a TagContext from the provided Context object.
     * If Context is not provided, the current Context is used. If no TagContext
     * is found, a new empty TagContext is returned.
     *
     * @param Context $ctx The Context to extract the TagContext from.
     * @return TagContext
     */
    final public static function fromContext(Context $ctx = null): TagContext
    {
        if ($ctx === null) {
            $ctx = Context::current();
        }
        return $ctx->value(self::CTX_KEY, new self());
    }

    /**
     * Copy and return the TagContext as found in the provided Context object.
     * If Context is not provided, the current Context is used. If no TagContext
     * is found, a new empty TagContext is returned.
     *
     * @param Context $ctx The Context to extract and copy the TagContext from.
     * @return TagContext
     */
    final public static function new(Context $ctx = null): TagContext
    {
        return clone self::fromContext($ctx);
    }
    /**
     * Creates a new Context with our TagContext attached.
     * To propagate a TagContext to downstream methods and downstream RPCs, add a
     * TagContext to the current Context. If there is already a TagContext in
     * the current context, it will be replaced with this TagContext.
     *
     * @param Context $ctx The source context to copy.
     * @return Context The target context with our TagContext added.
     */
    final public function newContext(Context $ctx = null): Context
    {
        if ($ctx === null) {
            $ctx = Context::current();
        }
        return $ctx->withValue(self::CTX_KEY, $this);
    }
}
