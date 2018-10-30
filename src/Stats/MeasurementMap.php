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
use OpenCensus\Core\DaemonClient;
use OpenCensus\Tags\TagContext;
use OpenCensus\Stats\Stats;
use OpenCensus\Stats\Measurement;
use OpenCensus\Stats\MeasurementInterface;

class MeasurementMap implements MeasurementInterface
{
    /** @var Measurement[] $measurements array of Measurement */
    private $measurements = array();

    /** @var array $attachments array of key-value pairs for Exemplars. */
    private $attachments = array();

    /**
     * Add a Measurement to our map.
     *
     * @param Measurement
     * @return void
     */
    public function put(Measurement $measurement)
    {
        $this->measurements[] = $measurement;
    }

    /**
     * Add an Exemplar Attachment to our map. If the key already exists, the
     * existing Attachment in the map will be overriden with the new value.
     *
     * @param string $key Attachment key.
     * @param string $value Attachment value.
     */
    public function putAttachment(string $key, string $value)
    {
        $this->attachments[$key] = $value;
    }

    /**
     * Record the Measurements, Attachments and Tags found in the map.
     * If Context is not explicitly provided, the current Context is used.
     * If a TagContext object is explicitly provided, all tags found are
     * inserted into the TagContext object found in Context. If a Tag with the
     * same key already exists in the implicit TagContext object, the explicit
     * Tag key value pair is used.
     */
    public function record(Context $ctx = null, TagContext $tags = null): bool
    {
        // without measurements we can bail out immediately
        if (count($this->measurements) === 0) return true;

        if ($ctx === null) {
            $ctx = Context::current();
        }
        // clone TagContext as found in Context so it isn't mutated by us adding
        // the record time only Tags as found in the provided $tags.
        $recordTags = clone TagContext::fromContext($ctx);
        if ($tags !== null) {
            foreach($tags as $key => $value) {
                $recordTags->upsert($key, $value);
            }
        }

        return Stats::exporter()->recordStats($this->measurements, $recordTags, $this->attachments);
    }
}
