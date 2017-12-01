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
 * A class that represents a MessageEvent resource.
 */
class MessageEvent extends TimeEvent
{
    const TYPE_UNSPECIFIED = 'TYPE_UNSPECIFIED';
    const TYPE_SENT = 'SENT';
    const TYPE_RECEIVED = 'RECEIVED';

    /**
     * @var string Type of MessageEvent. Indicates whether the message was sent
     *      or received.
     */
    private $type;

    /**
     * @var string An identifier for the MessageEvent's message that can be used
     *      to match SENT and RECEIVED MessageEvents. For example, this field
     *      could represent a sequence id for a streaming RPC. It is recommended
     *      to be unique within a Span.
     */
    private $id;

    /**
     * @var int The number of uncompressed bytes sent or received.
     */
    private $uncompressedSize;

    /**
     * @var int The number of compressed bytes sent or received. If missing
     *      assumed to be the same size as uncompressed.
     */
    private $compressedSize;

    /**
     * Create a new MessageEvent.
     *
     * @param string $type Type of MessageEvent. Indicates whether the message
     *        was sent or received.
     * @param string $id An identifier for the MessageEvent's message that can
     *        be used to match SENT and RECEIVED MessageEvents. For example,
     *        this field could represent a sequence id for a streaming RPC. It
     *        is recommended to be unique within a Span.
     * @param array $options [optional] Configuration options.
     *
     *      @type int $uncompressedSize The number of uncompressed bytes sent or
     *            received.
     *      @type int $compressedSize The number of compressed bytes sent or
     *            received. If missing assumed to be the same size as
     *            uncompressed.
     *      @type \DateTimeInterface|int|float $time The time of this event.
     */
    public function __construct($type, $id, $options = [])
    {
        $options += [
            'uncompressedSize' = null,
            'compressedSize' = null
        ];
        parent::__construct($options);
        $this->type = $type;
        $this->id = $id;
        $this->uncompressedSize = $options['uncompressedSize'];
        $this->compressedSize = $options['compressedSize'];
    }

    /**
     * Return the type of this message event.
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Return the id of this message event.
     *
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Return the uncompressed size of this message event.
     *
     * @return int
     */
    public function uncompressedSize()
    {
        return $this->uncompressedSize;
    }

    /**
     * Return the compressed size of this message event.
     *
     * @return int
     */
    public function compressedSize()
    {
        return $this->compressedSize;
    }
}
