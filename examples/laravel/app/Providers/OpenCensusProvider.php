<?php
/**
 * Copyright 2017 OpenCensus Authors
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

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenCensus\Trace\Exporter\StackdriverExporter;
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Integrations\Laravel;
use OpenCensus\Trace\Integrations\Mysql;
use OpenCensus\Trace\Integrations\PDO;

class OpenCensusProvider extends ServiceProvider
{
    public function boot()
    {
        if (php_sapi_name() == 'cli') {
            return;
        }

        // Enable OpenCensus extension integrations
        Laravel::load();
        Mysql::load();
        PDO::load();

        // Start the request tracing for this request
        Tracer::start(new StackdriverExporter());

        // Create a span that starts from when Laravel first boots (public/index.php)
        Tracer::inSpan(['name' => 'bootstrap', 'startTime' => LARAVEL_START], function () {});
    }
}
