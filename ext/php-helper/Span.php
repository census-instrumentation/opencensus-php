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

namespace OpenCensus\Trace\Ext;

/**
 * This is the equivalent PHP class created by the opencensus C extension
 */
class Span {

    public function name(): string
    {
        return '';
    }

    public function spanId(): ?string
    {
        return null;
    }

    public function parentSpanId(): ?string
    {
        return null;
    }

    public function startTime(): ?\DateTimeInterface
    {
        return null;
    }

    public function endTime(): ?\DateTimeInterface
    {
        return null;
    }

    public function attributes(): array
    {
        return [];
    }

    public function stackTrace(): array
    {
        return [];
    }

    public function links(): array
    {
        return [];
    }

    public function timeEvents(): array
    {
        return [];
    }

    public function kind(): string
    {
        return '';
    }
}
