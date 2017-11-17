<?php

namespace AppBundle;

use OpenCensus\Trace\Exporter\EchoExporter;
use OpenCensus\Trace\Integrations\Mysql;
use OpenCensus\Trace\Integrations\PDO;
use OpenCensus\Trace\Integrations\Symfony;
use OpenCensus\Trace\Tracer;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    public function boot()
    {
        $this->setupOpenCensus();
    }

    private function setupOpenCensus()
    {
        if (php_sapi_name() == 'cli') {
            return;
        }

        // Enable OpenCensus extension integrations
        Mysql::load();
        PDO::load();
        Symfony::load();

        // Start the request tracing for this request
        Tracer::start(new EchoExporter());
    }
}
