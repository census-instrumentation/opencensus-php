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

use OpenCensus\Trace\TraceContext;

/**
 * This interface lets us define separate TraceContext Propagator formats. This interface is responsible for
 * parsing and propagating the TraceContext to upstream and downstream requests.
 */
interface PropagatorInterface
{
    /**
     * Extract the TraceContext from some container
     *
     * @param mixed $container
     * @return TraceContext
     */
    public function extract($container);

    /**
     * Inject the TraceContext back into the response
     *
     * @param TraceContext $context
     * @param mixed $container
     * @return bool
     */
    public function inject(TraceContext $context, $container);

    /**
     * Fetch the formatter for propagating the TraceContext
     *
     * @return FormatterInterface
     */
    public function formatter();

    /**
     * Return the key used to propagate the TraceContext
     *
     * @return string
     */
    public function key();
}
