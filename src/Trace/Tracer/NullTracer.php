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

namespace OpenCensus\Trace\Tracer;

use OpenCensus\Core\Scope;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Span;

/**
 * This implementation of the TracerInterface is the null object implementation.
 * All methods are no ops. This tracer should be used if tracing is disabled.
 */
class NullTracer implements TracerInterface
{
    /**
     * Instrument a callable by creating a Span
     *
     * @param array $spanOptions Options for the span.
     *      {@see OpenCensus\Trace\Span::__construct()}
     * @param callable $callable The callable to instrument.
     * @param array $arguments [optional] Arguments for the callable.
     * @return mixed The result of the callable
     */
    public function inSpan(array $spanOptions, callable $callable, array $arguments = [])
    {
        return call_user_func_array($callable, $arguments);
    }

    /**
     * Start a new Span. The start time is already set to the current time.
     * The newly created span is not attached to the current context.
     *
     * @param array $spanOptions [optional] Options for the span.
     *      {@see OpenCensus\Trace\Span::__construct()}
     * @return Span
     */
    public function startSpan(array $spanOptions)
    {
        return new Span();
    }

    /**
     * Attaches the provided span as the current span and returns a Scope
     * object which must be closed.
     *
     * @param Span $span
     * @return Scope
     */
    public function withSpan(Span $span)
    {
        return new Scope(function () {
        });
    }

    /**
     * Return the spans collected.
     *
     * @return Span[]
     */
    public function spans()
    {
        return [];
    }

    /**
     * Add a attribute to the current Span
     *
     * @param string $attribute
     * @param string $value
     */
    public function addAttribute($attribute, $value)
    {
    }

    /**
     * Add a attribute to the primary Span
     *
     * @param string $attribute
     * @param string $value
     */
    public function addRootAttribute($attribute, $value)
    {
    }

    /**
     * Whether or not this tracer is enabled.
     *
     * @return bool
     */
    public function enabled()
    {
        return false;
    }

    /**
     * Returns the current SpanContext
     *
     * @return SpanContext
     */
    public function spanContext()
    {
        return new SpanContext();
    }
}
