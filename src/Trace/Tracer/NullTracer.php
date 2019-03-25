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
    public function inSpan(array $spanOptions, callable $callable, array $arguments = [])
    {
        return call_user_func_array($callable, $arguments);
    }

    public function startSpan(array $spanOptions): Span
    {
        return new Span($spanOptions);
    }

    public function withSpan(Span $span): Scope
    {
        return new Scope(function () {
        });
    }

    public function spans(): array
    {
        return [];
    }

    public function addAttribute($attribute, $value, $options = []): void
    {
    }

    public function addAnnotation($description, $options = []): void
    {
    }

    public function addLink($traceId, $spanId, $options = []): void
    {
    }

    public function addMessageEvent($type, $id, $options = []): void
    {
    }

    public function spanContext(): SpanContext
    {
        return new SpanContext(null, null, false);
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
}
