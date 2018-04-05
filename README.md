# OpenCensus for PHP - A stats collection and distributed tracing framework

> [Census][census-org] for PHP. Census provides a framework to measure a
server's resource usage and collect performance stats. This repository contains
PHP related utilities and supporting software needed by Census.

[![CircleCI](https://circleci.com/gh/census-instrumentation/opencensus-php.svg?style=svg)](https://circleci.com/gh/census-instrumentation/opencensus-php)
[![Packagist](https://img.shields.io/packagist/v/opencensus/opencensus.svg)](https://packagist.org/packages/opencensus/opencensus)
![PHP-Version](https://img.shields.io/packagist/php-v/opencensus/opencensus.svg)

* [API Documentation][api-docs]
* [Integration Documentation][integration-docs]

## Installation & basic usage

1. Install the `opencensus/opencensus` package using [composer][composer]:

    ```bash
    $ composer require opencensus/opencensus:~0.2
    ```

    **IMPORTANT: Please ensure your version is >= 0.2.0**. There is a potential security
    vulnerability in < 0.2.0.

1. [Optional]: Install the `opencensus` extension from [PECL][pecl]:

    ```bash
    $ pecl install opencensus-alpha
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
| [MultiSampler][multi-sampler] | Check multiple samplers |
| [QpsSampler][qps-sampler] | Trace X requests per second. Requires a PSR-6 cache implementation |
| [ProbabilitySampler][probability-sampler] | Trace X percent of requests. |

If you would like to provide your own sampler, create a class that implements
`SamplerInterface`.

### Exporters

You can choose different exporters to send the collected traces to.

The provided exporters are:

| Class | Description | Dependency |
| ----- | ----------- | ---------- |
| [EchoExporter][echo-exporter] | Output the collected spans to stdout | |
| [FileExporter][file-exporter] | Output JSON encoded spans to a file | |
| [JaegerExporter][jaeger-exporter] | Report traces to Jaeger server via Thrift over UDP | [opencensus/opencensus-exporter-jaeger][jaeger-packagist] |
| [LoggerExporter][logger-exporter] | Exporter JSON encoded spans to a PSR-3 logger | |
| [NullExporter][null-exporter] | No-op | |
| [StackdriverExporter][stackdriver-exporter] | Report traces to Google Cloud Stackdriver Trace | |
| [ZipkinExporter][zipkin-exporter] | Report collected spans to a Zipkin server | |

If you would like to provide your own reporter, create a class that implements
`ExporterInterface`.

## Versioning

[![Packagist](https://img.shields.io/packagist/v/opencensus/opencensus.svg)](https://packagist.org/packages/opencensus/opencensus)

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

**Current Status:** Alpha


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
[api-docs]: https://census-instrumentation.github.io/opencensus-php/api
[integration-docs]: https://census-instrumentation.github.io/opencensus-php
[composer]: https://getcomposer.org/
[pecl]: https://pecl.php.net/
[never-sampler]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Sampler/NeverSampleSampler.html
[always-sampler]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Sampler/NeverSampleSampler.html
[multi-sampler]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Sampler/MultiSampler.html
[qps-sampler]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Sampler/NeverSampleSampler.html
[probability-sampler]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Sampler/NeverSampleSampler.html
[echo-exporter]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Exporter/EchoExporter.html
[file-exporter]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Exporter/FileExporter.html
[jaeger-exporter]: https://github.com/census-instrumentation/opencensus-php-exporter-jaeger/blob/master/src/JaegerExporter.php
[jaeger-packagist]: https://packagist.org/packages/opencensus/opencensus-exporter-jaeger
[logger-exporter]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Exporter/LoggerExporter.html
[null-exporter]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Exporter/NullExporter.html
[stackdriver-exporter]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Exporter/StackdriverExporter.html
[zipkin-exporter]: https://census-instrumentation.github.io/opencensus-php/api/OpenCensus/Trace/Exporter/ZipkinExporter.html
[semver]: http://semver.org/
