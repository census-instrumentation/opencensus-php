<?php declare(strict_types=1);

namespace OpenCensus\Trace\Propagator;

interface HeaderGetter
{
    /**
     * @param string $header Header name
     * @return string|null
     */
    public function get(string $header): ?string;
}
