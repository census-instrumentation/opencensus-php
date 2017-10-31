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

namespace OpenCensus\Trace\Propagator;

use OpenCensus\Trace\SpanContext;

/**
 * This interface lets us define serialization strategies for SpanContext.
 */
interface FormatterInterface
{
    /**
     * Generate a SpanContext object from the Trace Context header
     *
     * @param string $header
     * @return SpanContext
     */
    public function deserialize($header);

    /**
     * Convert a SpanContext to header string
     *
     * @param SpanContext $context
     * @return string
     */
    public function serialize(SpanContext $context);
}
