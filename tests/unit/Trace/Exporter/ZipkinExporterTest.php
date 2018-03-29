<?php
/**
 * Copyright 2017 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Tests\Unit\Trace\Exporter;

use OpenCensus\Trace\Exporter\ZipkinExporter;
use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanData;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class ZipkinExporterTest extends TestCase
{
    /**
     * @var SpanData[]
     */
    private $spans;

    public function setUp()
    {
        parent::setUp();
        $this->spans = array_map(function ($span) {
            return $span->spanData();
        }, [
            new Span([
                'traceId' => 'aaa',
                'name' => 'span',
                'startTime' => microtime(true),
                'endTime' => microtime(true) + 10
            ])
        ]);
    }

    /**
     * http://zipkin.io/zipkin-api/#/paths/%252Fspans/post
     */
    public function testFormatsTrace()
    {
        $exporter = new ZipkinExporter('myapp');
        $data = $exporter->convertSpans($this->spans);

        $this->assertInternalType('array', $data);
        foreach ($data as $span) {
            $this->assertRegExp('/[0-9a-z]{16}/', $span['id']);
            $this->assertRegExp('/[0-9a-z]{32}/', $span['traceId']);
            $this->assertInternalType('string', $span['name']);
            $this->assertInternalType('int', $span['timestamp']);
            $this->assertInternalType('int', $span['duration']);

            // make sure we have a JSON object, even when there is no tags
            $this->assertStringStartsWith('{', \json_encode($span['tags']));
            $this->assertStringEndsWith('}', \json_encode($span['tags']));

            foreach ($span['tags'] as $key => $value) {
                $this->assertInternalType('string', $key);
                $this->assertInternalType('string', $value);
            }
            $this->assertFalse($span['shared']);
            $this->assertFalse($span['debug']);
        }
    }

    /**
     * @dataProvider spanOptionsForKind
     */
    public function testSpanKind($spanOpts, $kind)
    {
        $span = new Span($spanOpts);
        $span->setStartTime();
        $span->setEndTime();
        $exporter = new ZipkinExporter('myapp');
        $spans = $exporter->convertSpans([$span->spanData()]);

        $this->assertEquals($kind, $spans[0]['kind']);
    }

    public function spanOptionsForKind()
    {
        return [
            [['name' => 'Recv.Span1'], 'SERVER'],
            [['name' => 'Sent.Span2'], 'CLIENT'],
            [['name' => 'span3', 'timeEvents' => [new MessageEvent(MessageEvent::TYPE_RECEIVED, '')]], 'SERVER'],
            [['name' => 'span4', 'timeEvents' => [new MessageEvent(MessageEvent::TYPE_SENT, '')]], 'CLIENT'],
            [['kind' => Span::KIND_SERVER], 'SERVER'],
            [['kind' => Span::KIND_CLIENT], 'CLIENT'],
            [['kind' => Span::KIND_UNSPECIFIED], null]
        ];
    }

    public function testSpanDebug()
    {
        $exporter = new ZipkinExporter('myapp');
        $spans = $exporter->convertSpans($this->spans, [
            'HTTP_X_B3_FLAGS' => '1'
        ]);

        $this->assertCount(1, $spans);
        $this->assertTrue($spans[0]['debug']);
    }

    public function testSpanShared()
    {
        $span = new Span(['parentSpanId' => 'abc']);
        $span->setStartTime();
        $span->setEndTime();

        $exporter = new ZipkinExporter('myapp');
        $spans = $exporter->convertSpans([$span->spanData()]);

        $this->assertCount(1, $spans);
        $this->assertTrue($spans[0]['shared']);
    }

    public function testEmptyTrace()
    {
        $exporter = new ZipkinExporter('myapp');
        $spans = $exporter->convertSpans([]);
        $this->assertEmpty($spans);
    }

    public function testSkipsIpv4()
    {
        $exporter = new ZipkinExporter('myapp');
        $spans = $exporter->convertSpans($this->spans);

        $endpoint = $spans[0]['localEndpoint'];
        $this->assertArrayNotHasKey('ipv4', $endpoint);
        $this->assertArrayNotHasKey('ipv6', $endpoint);
    }

    public function testSetsIpv4()
    {
        $exporter = new ZipkinExporter('myapp');
        $exporter->setLocalIpv4('1.2.3.4');
        $spans = $exporter->convertSpans($this->spans);

        $endpoint = $spans[0]['localEndpoint'];
        $this->assertArrayHasKey('ipv4', $endpoint);
        $this->assertEquals('1.2.3.4', $endpoint['ipv4']);
    }

    public function testSetsIpv6()
    {
        $exporter = new ZipkinExporter('myapp');
        $exporter->setLocalIpv6('2001:db8:85a3::8a2e:370:7334');
        $spans = $exporter->convertSpans($this->spans);

        $endpoint = $spans[0]['localEndpoint'];
        $this->assertArrayHasKey('ipv6', $endpoint);
        $this->assertEquals('2001:db8:85a3::8a2e:370:7334', $endpoint['ipv6']);
    }

    public function testSetsLocalEndpointPort()
    {
        $exporter = new ZipkinExporter('myapp', null, ['SERVER_PORT' => "80"]);
        $spans = $exporter->convertSpans($this->spans);

        $endpoint = $spans[0]['localEndpoint'];
        $this->assertArrayHasKey('port', $endpoint);
        $this->assertEquals(80, $endpoint['port']);
    }
}
