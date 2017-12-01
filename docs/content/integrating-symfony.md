---
title: "Integrating OpenCensus with Symfony"
date: "2017-11-30"
type: page
menu:
  main:
    parent: "Integrations"
---

## Symfony 3.3

1. To add OpenCensus to our Symfony application, we will create a `Bundle`
   In `src/AppBundle/OpenCensusBundle.php`:

    ```php
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
            $exporter = new StackdriverExporter();
            Tracer::start($exporter);
        }
    }
    ```

    In this example, we configured `StackdriverExporter`, but you can configure
    any exporter here. You can also enable any other integrations here.

1. Enable this `Bundle` by adding it to the list of bundles registered. In
   `app/AppKernel.php`:

    ```php
    <?php
    // in the `registerBundles()`
    ...
    $bundles = [
        ...
        new AppBundle\AppBundle(),
        new AppBundle\OpenCensusBundle(),
    ];
    ...
    ```
