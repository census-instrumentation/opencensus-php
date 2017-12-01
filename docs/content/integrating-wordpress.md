---
title: "Integrating OpenCensus with WordPress"
date: "2017-11-30"
type: page
menu:
  main:
    parent: "Integrations"
---

To add OpenCensus to your WordPress installation, ensure that you can use
composer with your instance of WordPress.

In your `wp-config.php`:

```php
<?php
// load composer dependencies
require_once('/path/to/vendor/autoload.php');

use OpenCensus\Trace\Exporter\StackdriverExporter;
use OpenCensus\Trace\Tracer;

OpenCensus\Trace\Integrations\Wordpress::load();
$exporter = new StackdriverExporter();
Tracer::start($exporter);
```

In this example, we configured `StackdriverExporter`, but you can configure any
exporter here. You can also enable any other integrations here.
