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
 * This interface lets us define separate SpanContext Propagator formats. This
 * interface is responsible for parsing and propagating the SpanContext to
 * upstream and downstream requests.
 */
interface PropagatorInterface
{
    /**
     * Extract the SpanContext from some container
     *
     * @param mixed $container
     * @return SpanContext
     */
    public function extract($container): SpanContext;

    /**
     * Inject the SpanContext back into the response
     *
     * @param SpanContext $context
     * @param mixed $container An object in which the SpanContext data will be injected.
     */
    public function inject(SpanContext $context, &$container): void;
}
