<?php

namespace AppBundle;

use OpenCensus\Trace\Exporter\StackdriverExporter;
use OpenCensus\Trace\Integrations\Mysql;
use OpenCensus\Trace\Integrations\PDO;
use OpenCensus\Trace\Integrations\Symfony;
use OpenCensus\Trace\Tracer;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    public function boot()
    {
    }
}
