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

namespace OpenCensus\Stats;

use OpenCensus\Stats\MeasurementInterface;

/**
 * NoopMeasurementMap is a Noop implementation for Measurement recording.
 */
class NoopMeasurementMap implements MeasurementInterface
{
    public function put(Measurement $measurement): MeasurementInterface
    {
        return $this;
    }

    public function putAttachment(string $key, string $value): MeasurementInterface
    {
        return $this;
    }

    public function record(Context $ctx = null, TagContext $tags = null): bool
    {
        return true;
    }
}
