# OpenCensus - A stats collection and distributed tracing framework

This is the open-source release of Census for PHP. Census provides a
framework to measure a server's resource usage and collect performance stats.
This repository contains PHP related utilities and supporting software needed by
Census.

## Installation

### PHP library

1. Install with `composer` or add to your `composer.json`.

```
$ composer require opencensus/opencensus
```

2. Include and start the library as the first action in your application:

```php
use OpenCensus\Trace\RequestTracer;
use OpenCensus\Trace\Exporter\EchoExporter;

RequestTracer::start(new EchoExporter());
```

### PHP Extension

1. Install the `opencensus` extension from PECL.

```
$ pecl install opencensus
```

2. Enable the extension in your `php.ini`.

```
extension=opencensus.so
```

## Customizing

### Reporting Traces

The above sample uses the `EchoExporter` to dump trace results to the
bottom of the webpage.

If you would like to provide your own reporter, create a class that implements `ExporterInterface`.

#### Currently implemented reporters

| Class | Description |
| ----- | ----------- |
| [EchoExporter](src/Trace/Exporter/EchoExporter.php) | Output the collected spans to stdout |
| [FileExporter](src/Trace/Exporter/FileExporter.php) | Output JSON encoded spans to a file |
| [GoogleCloudExporter](src/Trace/Exporter/GoogleCloudExporter.php) | Report traces to Google Cloud Stackdriver Trace |
| [LoggerExporter](src/Trace/Exporter/LoggerExporter.php) | Exporter JSON encoded spans to a PSR-3 logger |
| [NullExporter](scr/Trace/Exporter/NullExporter.php) | No-op |
| [ZipkinExporter](src/Trace/Exporter/ZipkinExporter.php) | Report collected spans to a Zipkin server |

### Sampling Rate

By default we attempt to trace all requests. This is not ideal as it adds a little bit of
latency and require more memory for requests that are traced. To trace only a sampling
of requests, configure a sampler.

The preferred sampler is the `QpsSampler` (Queries Per Second). This sampler implementation
requires a PSR-6 cache implementation to function.

```php
use OpenCensus\Trace\Exporter\EchoExporter;
use OpenCensus\Trace\Sampler\QpsSampler;

$cache = new SomeCacheImplementation();
$sampler = new QpsSampler($cache, ['rate' => 0.1]); // sample 0.1 requests per second
RequestTracer::start(new EchoExporter(), ['sampler' => $sampler]);
```

Please note: While required for the `QpsSampler`, a PSR-6 implementation is
not included in this library. It will be necessary to include a separate
dependency to fulfill this requirement. For PSR-6 implementations, please see the
[Packagist PHP Package Repository](https://packagist.org/providers/psr/cache-implementation).
If the APCu extension is available (available on Google AppEngine Flexible Environment)
and you include the cache/apcu-adapter composer package, we will set up the cache for you.

You can also choose to use the `ProbabilitySampler` which simply samples a flat
percentage of requests.

#### Currently implemented samplers

| Class | Description |
| ----- | ----------- |
| [NeverSampleSampler](src/Trace/Sampler/NeverSampleSampler.php) | Never trace any requests |
| [AlwaysSampleSampler](src/Trace/Sampler/AlwaysSampleSampler.php) | Trace all requests |
| [QpsSampler](src/Trace/Sampler/QpsSampler.php) | Trace X requests per second. Requires a PSR-6 cache implementation |
| [ProbabilitySampler](src/Trace/Sampler/ProbabilitySampler.php) | Trace X percent of requests. |

```php
use OpenCensus\Trace\Exporter\EchoExporter;
use OpenCensus\Trace\Sampler\ProbabilitySampler;

$sampler = new ProbabilitySampler(0.1); // sample 10% of requests
RequestTracer::start(new EchoExporter(), ['sampler' => $sampler]);
```

If you would like to provide your own sampler, create a class that implements `SamplerInterface`.

## Tracing Code Blocks

To add tracing to a block of code, you can use the closure/callable form or explicitly open
and close spans yourself.

### Closure/Callable (preferred)

```php
$pi = RequestTracer::inSpan(['name' => 'expensive-operation'], function() {
    // some expensive operation
    return calculatePi(1000);
});

$pi = RequestTracer::inSpan(['name' => 'expensive-operation'], 'calculatePi', [1000]);
```

### Explicit Span Management

```php
RequestTracer::startSpan(['name' => 'expensive-operation']);
try {
    $pi = calculatePi(1000);
} finally {
    // Make sure we close the span to avoid mismatched span boundaries
    RequestTracer::endSpan();
}
```

### OpenCensus extension

The `opencensus` extension collects nested span data throughout the course of your application's
execution. You can collect spans in 2 ways, by watching for function/method invocations or by manually
starting and stopping spans. In both cases, the spans will be collected together and can be retrieved
at the end of the request.

See [extension README](ext/README.md) for more information.

## Versioning

You can retrieve the version of this extension at runtime.

```php
/**
 * Return the current version of the opencensus_trace extension
 *
 * @return string
 */
function opencensus_trace_version();
```

This library follows [Semantic Versioning](http://semver.org/).

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
