<?php
/**
 * Copyright 2017 Google Inc. All Rights Reserved.
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
 * This plain PHP class represents a single timed event within a Trace. Spans can
 * be nested and form a trace tree. Often, a trace contains a root span that
 * describes the end-to-end latency of an operation and, optionally, one or more subspans
 * for its suboperations. Spans do not need to be contiguous. There may be
 * gaps between spans in a trace.
 */
class TraceSpan
{
    /**
     * @var array Associative array containing all the fields representing this Span.
     */
    private $info = [];

    /**
     * Instantiate a new Span instance.
     *
     * @param array $options [optional] {
     *      Configuration options.
     *
     *      @type int $spanId The ID of the span. If not provided,
     *            one will be generated automatically for you.
     *      @type string $name The name of the span.
     *      @type \DateTimeInterface|int|float|string $startTime Start time of the span in nanoseconds.
     *            If provided as a string, it must be in "Zulu" format. If provided as an int or float, it is
     *            expected to be a Unix timestamp.
     *      @type \DateTimeInterface|int|float|string $endTime End time of the span in nanoseconds.
     *            If provided as a string, it must be in "Zulu" format. If provided as an int or float, it is
     *            expected to be a Unix timestamp.
     *      @type int $parentSpanId ID of the parent span if any.
     *      @type array $labels Associative array of $label => $value
     *            to attach to this span.
     * }
     */
    public function __construct($options = [])
    {
        if (array_key_exists('startTime', $options)) {
            $this->setStartTime($options['startTime']);
            unset($options['startTime']);
        }
        if (array_key_exists('endTime', $options)) {
            $this->setEndTime($options['endTime']);
            unset($options['endTime']);
        }

        if (array_key_exists('labels', $options)) {
            $this->addLabels($options['labels']);
            unset($options['labels']);
        }

        if (array_key_exists('spanId', $options)) {
            $this->info['spanId'] = $options['spanId'];
            unset($options['spanId']);
        } else {
            $this->info['spanId'] = $this->generateSpanId();
        }

        if (array_key_exists('name', $options)) {
            $this->info['name'] = $options['name'];
            unset($options['name']);
        } else {
            $this->info['name'] = $this->generateSpanName();
        }

        if (array_key_exists('parentSpanId', $options)) {
            $this->info['parentSpanId'] = $options['parentSpanId'];
            unset($options['parentSpanId']);
        }

        $this->info['metadata'] = $options;
    }

    /**
     * Retrieve the start time for this span.
     *
     * @return \DateTimeInterface
     */
    public function startTime()
    {
        return $this->info['startTime'];
    }

    /**
     * Set the start time for this span.
     *
     * @param  \DateTimeInterface|int|float|string $when [optional] The start time of this span.
     *         **Defaults to** now. If provided as a string, it must be in "Zulu" format.
     *         If provided as an int or float, it is expected to be a Unix timestamp.
     */
    public function setStartTime($when = null)
    {
        $this->info['startTime'] = $this->formatDate($when);
    }

    /**
     * Retrieve the end time for this span.
     *
     * @return \DateTimeInterface
     */
    public function endTime()
    {
        return $this->info['endTime'];
    }

    /**
     * Set the end time for this span.
     *
     * @param  \DateTimeInterface|int|float|string $when [optional] The end time of this span.
     *         **Defaults to** now. If provided as a string, it must be in "Zulu" format.
     *         If provided as an int or float, it is expected to be a Unix timestamp.
     */
    public function setEndTime($when = null)
    {
        $this->info['endTime'] = $this->formatDate($when);
    }

    /**
     * Retrieve the ID of this span.
     *
     * @return int
     */
    public function spanId()
    {
        return $this->info['spanId'];
    }

    /**
     * Retrieve the ID of this span's parent if it exists.
     *
     * @return int
     */
    public function parentSpanId()
    {
        return array_key_exists('parentSpanId', $this->info)
            ? $this->info['parentSpanId']
            : null;
    }

    /**
     * Retrieve the name of this span.
     *
     * @return string
     */
    public function name()
    {
        return $this->info['name'];
    }

    public function labels()
    {
        return $this->info['labels'] ?: [];
    }

    /**
     * Returns a serializable array representing this span.
     *
     * @return array
     */
    public function info()
    {
        return $this->info;
    }

    /**
     * Attach labels to this span.
     *
     * @param array $labels Labels in the form of $label => $value
     */
    public function addLabels(array $labels)
    {
        foreach ($labels as $label => $value) {
            $this->addLabel($label, $value);
        }
    }

    /**
     * Attach a single label to this span.
     *
     * @param string $label The name of the label.
     * @param mixed $value The value of the label. Will be cast to a string
     */
    public function addLabel($label, $value)
    {
        if (!array_key_exists('labels', $this->info)) {
            $this->info['labels'] = [];
        }
        $this->info['labels'][$label] = (string) $value;
    }

    /**
     * Returns a "Zulu" formatted string representing the provided \DateTime.
     *
     * @param  \DateTimeInterface|int|float $when [optional] The end time of this span.
     *         **Defaults to** now. If provided as a string, it must be in "Zulu" format.
     *         If provided as an int or float, it is expected to be a Unix timestamp.
     * @return \DateTimeInterface
     */
    private function formatDate($when = null)
    {
        if (!$when) {
            // now
            list($usec, $sec) = explode(' ', microtime());
            $micro = sprintf("%06d", $usec * 1000000);
            $when = new \DateTime(date('Y-m-d H:i:s.' . $micro));
        } elseif (is_numeric($when)) {
            // Expect that this is a timestamp
            $micro = sprintf("%06d", ($when - floor($when)) * 1000000);
            $when = new \DateTime(date('Y-m-d H:i:s.'. $micro, (int) $when));
        } elseif (!$when instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('Invalid date format. Must be a \DateTimeInterface or numeric.');
        }
        $when->setTimezone(new \DateTimeZone('UTC'));
        return $when;
    }

    /**
     * Generate a random ID for this span. Must be unique per trace,
     * but does not need to be globally unique.
     *
     * @return int
     */
    private function generateSpanId()
    {
        return mt_rand();
    }

    /**
     * Generate a name for this span. Attempts to generate a name
     * based on the caller's code.
     *
     * @return string
     */
    private function generateSpanName()
    {
        // Try to find the first stacktrace class entry that doesn't start with OpenCensus\Trace
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $bt) {
            $bt += ['line' => null];
            if (!array_key_exists('class', $bt)) {
                return implode('/', array_filter(['app', basename($bt['file']), $bt['function'], $bt['line']]));
            } elseif (substr($bt['class'], 0, 18) != 'OpenCensus\Trace') {
                return implode('/', array_filter(['app', $bt['class'], $bt['function'], $bt['line']]));
            }
        }

        // We couldn't find a suitable backtrace entry - generate a random one
        return uniqid('span');
    }
}
