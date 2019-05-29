<?php

namespace OpenCensus\Tests\Integration\Trace\Exporter;

use Elastica\Client;
use Elastica\Request;
use Elastica\Response;
use OpenCensus\Trace\Integrations\Elastica;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\ExporterInterface;
use PHPUnit\Framework\TestCase;

class ElasticaTest extends TestCase
{
    private $tracer;
    private static $elasticHost;
    private static $elasticPort;

    public static function setUpBeforeClass()
    {
        Elastica::load();
        self::$elasticHost = getenv('ELASTIC_HOST') ?: '127.0.0.1';
        self::$elasticPort = (int) (getenv('ELASTIC_PORT') ?: 9200);
    }

    public function setUp()
    {
        if (!extension_loaded('opencensus')) {
            $this->markTestSkipped('Please enable the opencensus extension.');
        }
        opencensus_trace_clear();
        $exporter = $this->prophesize(ExporterInterface::class);
        $this->tracer = Tracer::start($exporter->reveal(), [
            'skipReporting' => true
        ]);
    }

    private function getSpans()
    {
        $this->tracer->onExit();
        return $this->tracer->tracer()->spans();
    }

    public function testRequest()
    {
        $elastica = new Client([
            'host' => self::$elasticHost,
            'port' => self::$elasticPort
        ]);

        $request = new Request('_stats', Request::GET, [], [], $elastica->getConnection());
        $response = $request->send();

        $this->assertInstanceOf(Response::class, $response);
        $spans = $this->getSpans();
        $this->assertCount(2, $spans);
    }
}
