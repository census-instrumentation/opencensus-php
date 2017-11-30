---
date: "2017-10-30T11:18:27-08:00"
type: index
---

# Integrating OpenCensus with Silex

## Silex 2.2

To add OpenCensus to our Silex application, we simply start the tracer at the
beginning of our application. In `web/index.php`:

```php
require_once __DIR__ . '/../vendor/autoload.php';

// Configure and start the OpenCensus Tracer
$exporter = new OpenCensus\Trace\Exporter\StackdriverExporter();
OpenCensus\Trace\Tracer::start($exporter);

$app = new Silex\Application();
// ... rest of the application
```

In this example, we configured `StackdriverExporter`, but you can configure
any exporter here. You can also enable any other integrations here.
