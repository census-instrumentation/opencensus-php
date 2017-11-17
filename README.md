# OpenCensus for PHP - A stats collection and distributed tracing framework

> [Census][census-org] for PHP. Census provides a framework to measure a
server's resource usage and collect performance stats. This repository contains
PHP related utilities and supporting software needed by Census.

[![CircleCI](https://circleci.com/gh/census-instrumentation/opencensus-php.svg?style=svg)](https://circleci.com/gh/census-instrumentation/opencensus-php)

* [API Documentation][api-docs]

## Installation & basic usage

1. Install the `opencensus/opencensus` package using [composer][composer]:

```bash
$ composer require opencensus/opencensus
```

1. [Optional]: Install the `opencensus` extension from [PECL][pecl]:

```bash
$ pecl install opencensus-devel
```
   Enable the extension in your `php.ini`:

```ini
extension=opencensus.so
```

1. Initialize a tracer for your application:

```php
use OpenCensus\Trace\Tracer;
use OpenCensus\Trace\Exporter\EchoExporter;

Tracer::start(new EchoExporter());
```

## Usage

To add tracing to a block of code, you can use the closure/callable form or
explicitly open and close spans yourself.

### Closure/Callable (preferred)

```php
$pi = Tracer::inSpan(['name' => 'expensive-operation'], function() {
    // some expensive operation
    return calculatePi(1000);
});

$pi = Tracer::inSpan(['name' => 'expensive-operation'], 'calculatePi', [1000]);
```

### Explicit Span Management

```php
// Creates a detached span
$span = Tracer::startSpan(['name' => 'expensive-operation']);

// Opens a scope that attaches the span to the current context
$scope = Tracer::withSpan($span);
try {
    $pi = calculatePi(1000);
} finally {
    // Closes the scope (ends the span)
    $scope->close();
}
```

## Customization

### Samplers

You can specify different samplers when initializing a tracer. The default
sampler is the `AlwaysSampleSampler` which will attempt to trace all requests.

The provided samplers are:

| Class | Description |
| ----- | ----------- |
| [NeverSampleSampler][never-sampler] | Never trace any requests |
| [AlwaysSampleSampler][always-sampler] | Trace all requests |
| [QpsSampler][qps-sampler] | Trace X requests per second. Requires a PSR-6 cache implementation |
| [ProbabilitySampler][probability-sampler] | Trace X percent of requests. |

If you would like to provide your own sampler, create a class that implements
`SamplerInterface`.

### Exporters

You can choose different exporters to send the collected traces to.

The provided exporters are:

| Class | Description |
| ----- | ----------- |
| [EchoExporter][echo-exporter] | Output the collected spans to stdout |
| [FileExporter][file-exporter] | Output JSON encoded spans to a file |
| [StackdriverExporter][stackdriver-exporter] | Report traces to Google Cloud Stackdriver Trace |
| [LoggerExporter][logger-exporter] | Exporter JSON encoded spans to a PSR-3 logger |
| [NullExporter][null-exporter] | No-op |
| [ZipkinExporter][zipkin-exporter] | Report collected spans to a Zipkin server |

If you would like to provide your own reporter, create a class that implements
`ExporterInterface`.

## Versioning

This library follows [Semantic Versioning][semver].

Please note it is currently under active development. Any release versioned
0.x.y is subject to backwards incompatible changes at any time.

**GA**: Libraries defined at a GA quality level are stable, and will not
introduce backwards-incompatible changes in any minor or patch releases. We will
address issues and requests with the highest priority.

**Beta**: Libraries defined at a Beta quality level are expected to be mostly
stable and we're working towards their release candidate. We will address issues
and requests with a higher priority.

**Alpha**: Libraries defined at an Alpha quality level are still a
work-in-progress and are more likely to get backwards-incompatible updates.

## Contributing

Contributions to this library are always welcome and highly encouraged.

See [CONTRIBUTING](CONTRIBUTING.md) for more information on how to get started.

## Releasing

See [RELEASING](RELEASING.md) for more information on releasing new versions.

## License

Apache 2.0 - See [LICENSE](LICENSE) for more information.

## Disclaimer

This is not an official Google product.

[census-org]: https://github.com/census-instrumentation
[api-docs]: http://opencensus.io/opencensus-php/
[composer]: https://getcomposer.org/
[pecl]: https://pecl.php.net/
[never-sampler]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Sampler/NeverSampleSampler.html
[always-sampler]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Sampler/NeverSampleSampler.html
[qps-sampler]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Sampler/NeverSampleSampler.html
[probability-sampler]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Sampler/NeverSampleSampler.html
[echo-exporter]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Exporter/EchoExporter.html
[file-exporter]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Exporter/FileExporter.html
[stackdriver-exporter]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Exporter/StackdriverExporter.html
[logger-exporter]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Exporter/LoggerExporter.html
[null-exporter]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Exporter/NullExporter.html
[zipkin-exporter]: http://opencensus.io/opencensus-php/OpenCensus/Trace/Exporter/ZipkinExporter.html
[semver]: http://semver.org/
