<?php declare(strict_types=1);

namespace OpenCensus\Trace\Propagator;

class ArrayHeaders implements HeaderSetter, HeaderGetter, \IteratorAggregate, \ArrayAccess
{
    /**
     * @var string[]
     */
    private $headers;

    /**
     * @param string[] $headers An associative array with header name as key
     */
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }

    public function get(string $header): ?string
    {
        return $this->headers[$header] ?? null;
    }

    public function set(string $header, string $value): void
    {
        $this->headers[$header] = $value;
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->headers);
    }

    public function offsetExists($offset)
    {
        return isset($this->headers[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->headers[$offset]);
    }
}
