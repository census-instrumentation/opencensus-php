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

use OpenCensus\Core\Context;
use OpenCensus\Tags\TagContext;
use OpenCensus\Stats\Measurement;

interface MeasurementInterface
{
    /**
     * Add a Measurement to our map.
     *
     * @param Measurement
     * @return void
     */
    public function put(Measurement $measurement);

    /**
     * Add an Exemplar Attachment to our map. If the key already exists, the
     * existing Attachment in the map will be overriden with the new value.
     *
     * @param string $key Attachment key.
     * @param string $value Attachment value.
     */
    public function putAttachment(string $key, string $value);

    /**
     * Record the Measurements, Attachments and Tags found in the map.
     * If Context is not explicitly provided, the current Context is used.
     * If a TagContext object is explicitly provided, all tags found are
     * inserted into the TagContext object found in Context. If a Tag with the
     * same key already exists in the implicit TagContext object, the explicit
     * Tag key value pair is used.
     */
    public function record(Context $ctx = null, TagContext $tags = null): bool;
}
