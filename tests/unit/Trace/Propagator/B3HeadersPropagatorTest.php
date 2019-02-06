<?php

namespace OpenCensus\Tests\Unit\Trace\Propagator;

use OpenCensus\Trace\Propagator\B3HeadersPropagator;
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;
use OpenCensus\Trace\SpanContext;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class B3HeadersPropagatorTest extends TestCase
{
    /**
     * @dataProvider traceMetadata
     */
    public function testExtract($traceId, $spanId, $enabled, $headers)
    {
        $propagator = new B3HeadersPropagator();
        $context = $propagator->extract($headers);
        $this->assertEquals($traceId, $context->traceId());
        $this->assertEquals($spanId, $context->spanId());
        $this->assertEquals($enabled, $context->enabled());
        $this->assertTrue($context->fromHeader());
    }

    public function testExtractWithoutHeaders()
    {
        $propagator = new B3HeadersPropagator();
        $context = $propagator->extract([]);
        $this->assertNull($context->enabled());
        $this->assertFalse($context->fromHeader());
    }

    /**
     * @dataProvider traceMetadata
     */
    public function testInject($traceId, $spanId, $enabled, $headers)
    {
        $propagator = new B3HeadersPropagator();
        $context = new SpanContext($traceId, $spanId, $enabled);
        $output = $propagator->inject($context, []);

        $this->assertArrayHasKey('X-B3-TraceId', $output);
        $this->assertArrayHasKey('X-B3-SpanId', $output);
        $this->assertArrayHasKey('X-B3-Sampled', $output);

        $sampled = $enabled ? '1' : '0';

        $this->assertEquals($traceId, $output['X-B3-TraceId']);
        $this->assertEquals($spanId, $output['X-B3-SpanId']);
        $this->assertEquals($sampled, $output['X-B3-Sampled']);
    }

    public function traceMetadata()
    {
        return [
            [
                '463ac35c9f6413ad48485a3953bb6124',
                'a2fb4a1d1a96d312',
                true,
                [
                    'X-B3-TraceId' => '463ac35c9f6413ad48485a3953bb6124',
                    'X-B3-SpanId' => 'a2fb4a1d1a96d312',
                    'X-B3-Sampled' => '1',
                ],
            ],
            [
                '463ac35c9f6413ad48485a3953bb6124',
                'a2fb4a1d1a96d312',
                true,
                [
                    'X-B3-TraceId' => '463ac35c9f6413ad48485a3953bb6124',
                    'X-B3-SpanId' => 'a2fb4a1d1a96d312',
                    'X-B3-Sampled' => 'true',
                ],
            ],
            [
                '463ac35c9f6413ad48485a3953bb6124',
                'a2fb4a1d1a96d312',
                false,
                [
                    'X-B3-TraceId' => '463ac35c9f6413ad48485a3953bb6124',
                    'X-B3-SpanId' => 'a2fb4a1d1a96d312',
                    'X-B3-Sampled' => '0',
                ],
            ],
            [
                '463ac35c9f6413ad48485a3953bb6124',
                'a2fb4a1d1a96d312',
                false,
                [
                    'X-B3-TraceId' => '463ac35c9f6413ad48485a3953bb6124',
                    'X-B3-SpanId' => 'a2fb4a1d1a96d312',
                    'X-B3-Sampled' => 'false',
                ],
            ],
            [
                '463ac35c9f6413ad48485a3953bb6124',
                'a2fb4a1d1a96d312',
                true,
                [
                    'X-B3-TraceId' => '463ac35c9f6413ad48485a3953bb6124',
                    'X-B3-SpanId' => 'a2fb4a1d1a96d312',
                    'X-B3-Flags' => '1',
                ],
            ],
        ];
    }
}
