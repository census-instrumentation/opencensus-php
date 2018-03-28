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

namespace OpenCensus\Trace\Sampler;

/**
 * This implementation of the SamplerInterface wraps any number of child
 * SamplerInterface implementations. All provided implementations must return
 * true in order for the request to be sampled.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Sampler\AlwaysSampleSampler;
 * use OpenCensus\Trace\Sampler\MultiSampler;
 *
 * $sampler = new MultiSampler([
 *   new AlwaysSampleSampler()
 * ]);
 * ```
 */
class MultiSampler implements SamplerInterface
{

    /**
     * @var SamplerInterface[]
     */
    private $samplers;

    /**
     * Create a new MultiSampler.
     *
     * @param SamplerInterface[] $samplers The samplers to consult.
     */
    public function __construct(array $samplers = [])
    {
        $this->samplers = $samplers;
    }

    /**
     * Returns false if any provided sampler chooses not to sample this
     * request.
     *
     * @return bool
     */
    public function shouldSample()
    {
        foreach ($this->samplers as $sampler) {
            if ($sampler->shouldSample() === false) {
                return false;
            }
        }
        return true;
    }
}
