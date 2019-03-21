<?php declare(strict_types=1);

namespace OpenCensus\Tests\Unit\Utils;

use OpenCensus\Utils\IdGenerator;
use PHPUnit\Framework\TestCase;

class IdGeneratorTest extends TestCase
{
    public function testLengthGreaterThanZero()
    {
        $data = IdGenerator::hex(8);
        $this->assertTrue(ctype_xdigit($data)); // Assert it's hexadecimal
        $this->assertSame(16, strlen($data)); // It's 16 hex characters, 8 bytes
    }

    public function testLengthZero()
    {
        $data = IdGenerator::hex(0);
        $this->assertSame('', $data);
    }

    public function testLengthNegative()
    {
        $data = IdGenerator::hex(-1);
        $this->assertSame('', $data);
    }
}
