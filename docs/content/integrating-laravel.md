---
title: "Integrating OpenCensus with Laravel"
date: "2017-11-30"
type: page
menu:
  main:
    parent: "Integrations"
---

## Laravel 5.5

1. To add OpenCensus to our Laravel application, we will create a
   `ServiceProvider`. In `app/Providers/OpenCensusProvider.php`:

    ```php
    <?php
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
            $exporter = new StackdriverExporter();
            Tracer::start($exporter);

            // Create a span that starts from when Laravel first boots (public/index.php)
            Tracer::inSpan(['name' => 'bootstrap', 'startTime' => LARAVEL_START], function () {});
        }
    }
    ```

    In this example, we configured `StackdriverExporter`, but you can configure
    any exporter here. You can also enable any other integrations here.

1. Enable this `ServiceProvider`. In `config/app.php`:

    ```php
    <?php
    // in the `providers` section
    ...
    'providers' => [
        ...
        App\Providers\OpenCensusProvider::class,
    ],
    ...
    ```
