# Example Silex Application (2.2)

## Integrating OpenCensus

To add OpenCensus to our Silex application, we simply start the tracer at the
beginning of our application. In `web/index.php`:

```php
require_once __DIR__ . '/../vendor/autoload.php';

// Configure and start the OpenCensus Tracer
$exporter = new OpenCensus\Trace\Exporter\EchoExporter();
OpenCensus\Trace\Tracer::start($exporter);

$app = new Silex\Application();
// ... rest of the application
```

In this example, we configured `EchoExporter`, but you can configure
any exporter here. You can also enable any other integrations here.
