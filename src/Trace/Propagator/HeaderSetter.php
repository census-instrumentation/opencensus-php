<?php declare(strict_types=1);

namespace OpenCensus\Trace\Propagator;

interface HeaderSetter
{
    /**
     * @param string $header Header name
     * @param string $value Header value
     */
    public function set(string $header, string $value);
}
