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

namespace OpenCensus\Trace\Sampler;

/**
 * This implementation of the SamplerInterface always returns false. Use this
 * sampler to attempt to trace all requests. You may be throttled by the server.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Sampler\AlwaysSampleSampler;
 *
 * $sampler = new AlwaysSampleSampler();
 * ```
 */
class AlwaysSampleSampler implements SamplerInterface
{
    /**
     * Returns true because we always want to sample.
     *
     * @return bool
     */
    public function shouldSample()
    {
        return true;
    }
}
