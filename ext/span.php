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
    protected $name = "unknown";
    protected $spanId;
    protected $parentSpanId;
    protected $startTime;
    protected $endTime;
    protected $attributes;
    protected $stackTrace;
    protected $links;
    protected $timeEvents;

    public function __construct(array $spanOptions)
    {
        foreach ($spanOptions as $k => $v) {
            $this->__set($k, $v);
        }
    }

    public function name()
    {
        return $this->name;
    }

    public function spanId()
    {
        return $this->spanId;
    }

    public function parentSpanId()
    {
        return $this->parentSpanId;
    }

    public function startTime()
    {
        return $this->startTime;
    }

    public function endTime()
    {
        return $this->endTime;
    }

    public function attributes()
    {
        return $this->attributes;
    }

    public function stackTrace()
    {
        return $this->stackTrace;
    }

    public function links()
    {
        return $this->links;
    }

    public function timeEvents()
    {
        return $this->timeEvents;
    }
}
