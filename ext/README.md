# OpenCensus PHP Extension (Alpha)

[OpenCensus](https://opencensus.io/) is a free, open-source distributed tracing
implementation based on the [Dapper Paper](https://research.google.com/pubs/pub36356.html).
This extension allows you to "watch" class method and function calls in order to
automatically collect nested spans (timing data).

This library can optionally work in conjunction with the PHP library
[opencensus/opencensus](https://packagist.org/packages/opencensus/opencensus) in order
to send collected span data to a backend storage server.

This extension also maintains the current trace span context - the current span
the code is currently executing within. Whenever a span is created, it's parent
is set the the current span, and this new span becomes the current trace span
context.

## Compatibilty

This extension has been built and tested on the following PHP versions:

* 7.0.x
* 7.1.x
* 7.2.x

## Installation

### Build from source

1. [Download a release](https://github.com/census-instrumentation/opencensus-php/releases)

   ```bash
   curl https://github.com/census-instrumentation/opencensus-php/archive/v0.0.4.tar.gz -o opencensus.tar.gz
   ```

1. Untar the package

   ```bash
   tar -zxvf opencensus.tar.gz
   ```

1. Go to the extension directory

   ```bash
   cd opencensus-php-0.1.1/ext
   ```

1. Compile the extension

   ```bash
   phpize
   configure --enable-opencensus
   make
   make test
   make install
   ```

1. Enable the opencensus trace extension. Add the following to your `php.ini` configuration file.

   ```
   extension=opencensus.so
   ```

### Download from PECL (not yet available)

When this extension is available on PECL, you will be able to download and install it easily using the
`pecl` CLI tool:

```bash
pecl install opencensus
```

## Usage

The `opencensus` extension collects nested span data throughout the course of your application's
execution. You can collect spans in 2 ways, by watching for function/method invocations or by manually
starting and stopping spans. In both cases, the spans will be collected together and can be retrieved
at the end of the request.

### Watching for function/method invocations

To trace a class method, use the `opencensus_trace_method`:

```php
/**
 * Trace each invocation of the specified function
 *
 * @param  string $className
 * @param  string $methodName
 * @param  array|Closure $handler
 * @return bool
 */
function opencensus_trace_method($className, $methodName, $handler = []);

// Example: create a span whenever a new instance of Foobar is created
opencensus_trace_method('Foobar', '__construct');
$foobar = new Foobar();
```

The `$handler` parameter can be either an array or a callable.

If an array is provided, it should be an associative array with the following optional keys:

* `name` - string - the name of the span to create. **Defaults to** the full method name (i.e. `Foobar::__construct`).
* `startTime` - float - the start time of the span. **Defaults to** the time that the method was invoked.
* `endTime` - float - the end time of the span. **Defaults to** the time that the method invocation completed.
* `attributes` - array - an associative array of string => string tags for this span.

If a callback is provided, it will be passed the instance of the class (scope) and a copy of each parameter
provided to the watched method. The callback should return an array with the above options. If the callback does
not return an array, an `E_USER_WARNING` error is thrown.

```php
// Example: supply a static array of span options as the handler
opencensus_trace_method('Foobar', '__construct', [
    'name' => 'Foobar::__construct',
    'attributes' => [
        'foo' => 'bar'
    ]
]);

// Example: supply a closure
opencensus_trace_method('Foobar', '__construct', function ($scope, $constructArg1, $constructArg2) {
    return [
        'attributes' => [
            'arg1' => $constructArg1
        ]
    ];
});

// Example: supply a callback
function my_callback($scope, $constructArg1, $constructArg2)
{
    return [
        'attributes' => [
            'arg1' => $constructArg1
        ]
    ];
}
opencensus_trace_method('Foobar', '__construct', 'my_callback');
```

To trace a function, use the `opencensus_trace_function`:

```php
/**
 * Trace each invocation of the specified function
 *
 * @param  string $functionName
 * @param  array|callback $handler
 * @return bool
 */
function opencensus_trace_function($functionName, $handler = []);

// Example: create a span whenever a new instance of Foobar is created
opencensus_trace_function('var_dump');
var_dump(123);
```

Just like tracing a method, you can provide a `$handler` option which can be an array or a callback. The behavior
is the same as the method tracing, except that the callback will not be passed the scope parameter as there is
not object scope available.

```php
opencensus_trace_function('var_dump', function ($value) {
    return [
        'name' => 'Foobar::__construct',
        'attributes' => [
          'value' => $value
        ]
    ];
});
```

### Manually creating trace spans

Manually start a trace span with name `$spanName`. `$spanOptions` is an associative array that matches
the format used in `opencensus_trace_method` and `opencensus_trace_function`.

```php
/**
 * Start a trace span. The current trace span (if any) will be set as this span's parent.
 *
 * @return bool Returns true if the span has been created
 */
opencensus_trace_begin($spanName, $spanOptions);
```

Manually finish the current trace span.

```php
/**
 * Finish the current trace span. The previous trace span (if any) will be set as the current trace span.
 *
 * @return bool Returns true if the span has been finished
 */
opencensus_trace_finish();
```

### Retrieving span data

Retrieve an array of collected spans. This returns an array of `OpenCensus\Trace\Ext\Span` instances. In general,
you will do this at the end of the request. See the [PHP equivalent code](span.php).

```php
/**
 * Retrieve the list of collected trace spans
 *
 * @return OpenCensus\Trace\Ext\Span[]
 */
function opencensus_trace_list();

/**
 * Clear the list of collected trace spans
 *
 * @return bool
 */
function opencensus_trace_clear();
```

### Maintaining context

As you create spans, your trace context is automatically maintained for you. Trace context consists of a `$traceId`
and current `$spanId`. At any point, you can ask for the current trace context. This returns a
`OpenCensus\Trace\Ext\SpanContext` object. See the [PHP equivalent code](span_context.php).

```php
/**
 * Fetch the current trace context
 *
 * @return OpenCensus\Trace\Ext\SpanContext
 */
function opencensus_trace_context();
```

You may also set the initial trace context. Note that doing this after spans have been created is undefined.

```php
/**
 * Set the initial trace context
 *
 * @param string $traceId The trace id for this request. **Defaults to** a generated value.
 * @param string $parentSpanId [optional] The span id of this request's parent. **Defaults to** `null`.
 */
function opencensus_trace_set_context($traceId, $parentSpanId = null);
```

### Add attributes to spans

```php
/**
 * Add an attribute to a span.
 *
 * @param string $key
 * @param string $value
 * @param array $options
 *
 *      @type int $spanId The id of the span to which to add the attribute.
 *            Defaults to the current span.
 */
function opencensus_trace_add_attribute($key, $value, $options = []);
```

### Add an annotation to a span.

```php
/**
 * Add an annotation to a span
 * @param string $description
 * @param array $options
 *
 *      @type int $spanId The id of the span to which to add the attribute.
 *            Defaults to the current span.
 */
function opencensus_trace_add_annotation($description, $options = []);
```

### Add a link to a span.

```php
/**
 * Add a link to a span
 * @param string $traceId
 * @param string $spanId
 * @param array $options
 *
 *      @type int $spanId The id of the span to which to add the link.
 *            Defaults to the current span.
 */
function opencensus_trace_add_link($traceId, $spanId, $options = []);
```

### Add a message event to a span.

```php
/**
 * Add a message to a span
 * @param string $type
 * @param string $id
 * @param array $options
 *
 *      @type int $spanId The id of the span to which to add the attribute.
 *            Defaults to the current span.
 */
function opencensus_trace_add_message_event($type, $id, $options = []);
```

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

## License

Apache 2.0 - See [LICENSE](LICENSE) for more information.
