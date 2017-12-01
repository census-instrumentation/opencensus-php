---
title: "Integrating OpenCensus with Silex"
date: "2017-11-30"
type: page
menu:
  main:
    parent: "Integrations"
---

## Silex 2.2

To add OpenCensus to our Silex application, we simply start the tracer at the
beginning of our application. In `web/index.php`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Configure and start the OpenCensus Tracer
$exporter = new OpenCensus\Trace\Exporter\StackdriverExporter();
OpenCensus\Trace\Tracer::start($exporter);

$app = new Silex\Application();
// ... rest of the application
```

In this example, we configured `StackdriverExporter`, but you can configure
any exporter here. You can also enable any other integrations here.
