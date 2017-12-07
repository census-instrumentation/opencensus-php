---
title: "Integrating OpenCensus with Guzzle"
date: "2017-11-30"
type: page
menu:
  main:
    parent: "Integrations"
---

Integration with Guzzle using the following methods will:

1. Create spans for every outgoing HTTP request used by that Guzzle client.
2. Propagate the span context to the remote endpoint for distributed tracing.

## Guzzle 6

To add OpenCensus support for Guzzle 6 HTTP clients, we add a middleware to our
Guzzle client:

```php
<?php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use OpenCensus\Trace\Integrations\Guzzle\Middleware;

$stack = new HandlerStack();
$stack->setHandler(\GuzzleHttp\choose_handler());
$stack->push(new Middleware());
$client = new Client(['handler' => $stack]);
```

You will want to set this up wherever your Guzzle client is created.

## Guzzle 5

To add OpenCensus support for Guzzle 5 clients, we attach an EventSubscriber to
our Guzzle client:

```php
<?php
use GuzzleHttp\Client;
use OpenCensus\Trace\Integrations\Guzzle\EventSubscriber;

$client = new Client();
$subscriber = new EventSubscriber();
$client->getEmitter()->attach($subscriber);
```

You will want to set this up wherever your Guzzle client is created.
