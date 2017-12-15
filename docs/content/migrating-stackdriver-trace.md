---
title: "Migrating to OpenCensus from Stackdriver Trace V1"
date: "2017-12-15"
type: page
menu:
  main:
    parent: "Migration Guide"
---

The Stackdriver Trace library (`google/cloud-trace`) prior to v0.4.0 (v0.47.0
for the umbrella `google/cloud` package) provided application tracing
integration. Version v0.4.0 removed this capability and instead defers to the
`opencensus/opencensus` package from application integration.

## Installation

1. Add OpenCensus to your package.json:

    ```
    $ composer require opencensus/opencensus
    ```

2. Ensure `google/cloud-trace` is >= v0.4.0 or `google/cloud` is >= 0.47.0:

    ```
    // in composer.json
    ...
    "require": {
        "google/cloud-trace": "^0.4.0",
        // OR
        "google/cloud": "^0.47.0"
    }
    ...
    ```

3. Update your composer packages:

    ```
    $ composer update
    ```

## Renamed Components

Between the Stackdriver Trace implementation and OpenCensus, most changes are
simple renames.

| Old Name | New Name |
| -------- | -------- |
| `Google\Cloud\Trace\RequestTracer` | `OpenCensus\Trace\Tracer` |
| `Google\Cloud\Trace\Sampler\AlwaysOffSampler` | `OpenCensus\Trace\Sampler\NeverSampleSampler` |
| `Google\Cloud\Trace\Sampler\AlwaysOnSampler` | `OpenCensus\Trace\Sampler\AlwaysSampleSampler` |
| `Google\Cloud\Trace\Sampler\QpsSampler` | `OpenCensus\Trace\Sampler\QpsSampler` |
| `Google\Cloud\Trace\Sampler\RandomSampler` | `OpenCensus\Trace\Sampler\ProbabilitySampler` |
| `Google\Cloud\Trace\Reporter\AsyncReporter` | `OpenCensus\Trace\Exporter\StackdriverExporter` |
| `Google\Cloud\Trace\Reporter\EchoReporter` | `OpenCensus\Trace\Exporter\EchoExporter` |
| `Google\Cloud\Trace\Reporter\FileReporter` | `OpenCensus\Trace\Exporter\FileExporter` |
| `Google\Cloud\Trace\Reporter\NullReporter` | `OpenCensus\Trace\Exporter\NullExporter` |
| `Google\Cloud\Trace\Reporter\SyncReporter` | `OpenCensus\Trace\Exporter\StackdriverExporter` |

## Setup

In Stackdriver Trace:

```php
<?php
use Google\Cloud\Trace\TraceClient;
use Google\Cloud\Trace\RequestTracer;

$trace = new TraceClient();
$reporter = $trace->reporter();
RequestTracer::start($reporter);
```
In OpenCensus:

```php
<?php
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\StackdriverExporter;

Tracer::start(new StackdriverExporter(['async' => true]));
```

## Configuration

### Sampler

If you configure a sampler, you can no longer specify sampler configuration as
an array -- you can only provide a `SamplerInterface` instance.

In Stackdriver Trace:

```php
<?php

RequestTracer::start($reporter, [
  'sampler' => [
    'type' => 'random',
    'rate' => 0.2
  ]
]);
```

In OpenCensus:

```php
<?php
use OpenCensus\Trace\Sampler\ProbabilitySampler;

Tracer::start($reporter, [
  'sampler' => new ProbabilitySampler(0.2)
]);
```
