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

namespace OpenCensus\Trace;

/**
 * A class that represents a Link resource.
 */
class Link
{
    use AttributeTrait;

    const TYPE_UNSPECIFIED = 'TYPE_UNSPECIFIED';
    const TYPE_CHILD_LINKED_SPAN = 'CHILD_LINKED_SPAN';
    const TYPE_PARENT_LINKED_SPAN = 'PARENT_LINKED_SPAN';

    /**
     * @var string `TRACE_ID` a unique identifier for a trace.
     */
    private $traceId;

    /**
     * @var string `SPAN_ID` a unique identifier for a span within a trace.
     */
    private $spanId;

    /**
     * @var string The relationship of the current span relative to the linked
     *      span: child, parent, or unspecified.
     */
    private $type;

    /**
     * Create a new Link.
     *
     * @param string $traceId `TRACE_ID` a unique identifier for a trace.
     * @param string $spanId `SPAN_ID` a unique identifier for a span within a
     *        trace.
     * @param array  $options [description] Configuration options
     *
     *      @type string $type The relationship of the current span relative to
     *            the linked span: child, parent, or unspecified.
     *      @type array $attributes Attributes for this annotation.
     *      @type \DateTimeInterface|int|float $time The time of this event.
     */
    public function __construct($traceId, $spanId, $options = [])
    {
        $options += [
            'type' => self::TYPE_UNSPECIFIED,
            'attributes' => []
        ];
        parent::__construct($options);
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->type = $options['type'];
        $this->setAttributes($options['attributes']);
    }

    /**
     * Return the traceId for this link.
     *
     * @return string
     */
    public function traceId()
    {
        return $this->traceId;
    }

    /**
     * Return the spanId for this link.
     *
     * @return string
     */
    public function spanId()
    {
        return $this->spanId;
    }

    /**
     * Return the type for this link.
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }
}
