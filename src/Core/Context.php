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

namespace OpenCensus\Core;

/**
 * This class is an implementation of a generic context.
 *
 * Example:
 * ```
 * // Create and set a new context, which inherits values from the current
 * // context and adds a new key/value pair of 'foo'/'bar'.
 * $prev = Context::current()->withValue('foo', 'bar')->attach();
 * try {
 *   // Do something within the context
 * } finally {
 *   // This makes sure that $prev will be current after this execution
 *   Context::current()->detach($prev);
 * }
 * // Here, we are 100% sure $prev is the current context.
 * ```
 */
class Context
{
    /**
     * @var Context
     */
    private static $current;

    /**
     * @var array
     */
    private $values;

    /**
     * Creates a new Context.
     *
     * @param array $initialValues
     */
    public function __construct($initialValues = [])
    {
        $this->values = $initialValues;
    }

    /**
     * Creates a new context with the given key/value set.
     *
     * @param string $key
     * @param mixed $value
     * @return Context
     */
    public function withValue($key, $value)
    {
        $copy = $this->values;
        $copy[$key] = $value;
        return new Context($copy);
    }

    /**
     * Creates a new context with the given key/values.
     *
     * @param array $data
     * @return Context
     */
    public function withValues($data)
    {
        $copy = $this->values;
        foreach ($data as $key => $value) {
            $copy[$key] = $value;
        }
        return new Context($copy);
    }

    /**
     * Fetches the value for a given key in this context. Returns the provided
     * default if not set.
     *
     * @param string $key
     * @param mixed $default [optional]
     * @return mixed
     */
    public function value($key, $default = null)
    {
        return array_key_exists($key, $this->values)
            ? $this->values[$key]
            : $default;
    }

    /**
     * Attaches this context, thus entering a new scope within which this
     * context is current(). The previously current context is returned.
     *
     * @return Context
     */
    public function attach()
    {
        $current = self::current();
        self::$current = $this;
        return $current;
    }

    /**
     * Reverses an attach(), restoring the previous context and exiting the
     * current scope.
     *
     * @param  Context $toAttach
     */
    public function detach(Context $toAttach)
    {
        if (self::current() !== $this) {
            trigger_error('Unexpected context to detach.', E_USER_WARNING);
        }

        self::$current = $toAttach;
    }

    /**
     * Returns all the contained data.
     *
     * @return array
     */
    public function values()
    {
        return $this->values;
    }

    /**
     * Returns the context associated with the current scope, will never return
     * null.
     *
     * @return Context
     */
    public static function current()
    {
        if (!self::$current) {
            self::$current = self::background();
        }

        return self::$current;
    }

    /**
     * Returns an empty context.
     *
     * @return Context
     */
    public static function background()
    {
        return new Context();
    }

    /**
     * Resets the context to an initial state. This is generally used only for
     * testing.
     *
     * @internal
     */
    public static function reset()
    {
        self::$current = null;
    }
}
