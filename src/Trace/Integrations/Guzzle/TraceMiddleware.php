<?php declare(strict_types=1);

namespace Hellofresh\Business\Infrastructure\OpenCensus;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Response;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use OpenCensus\Trace\Propagator\HttpHeaderPropagator;
use OpenCensus\Trace\Propagator\PropagatorInterface;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer\TracerInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Slightly modified version of OpenCensus Guzzle middleware
 */
final class TraceMiddleware
{
    /**
     * @var PropagatorInterface
     */
    private $propagator;
    /**
     * @var TracerInterface
     */
    private $tracer;
    /**
     * @var bool
     */
    private $logBody;

    public function __construct(TracerInterface $tracer, PropagatorInterface $propagator = null, bool $logBody = true)
    {
        $this->propagator = $propagator ?: new HttpHeaderPropagator();
        $this->tracer = $tracer;
        $this->logBody = $logBody;
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, $options) use ($handler) {
            $context = $this->tracer->spanContext();
            if ($context->enabled()) {
                $headers = new ArrayHeaders();
                $this->propagator->inject($context, $headers);
                foreach ($headers as $headerName => $headerValue) {
                    $request = $request->withHeader($headerName, $headerValue);
                }
            }

            $attrHeaders = [];
            foreach ($request->getHeaders() as $name => $values) {
                $attrHeaders['request.' . $name] = implode(', ', $values);
            }

            $span = $this->tracer->startSpan([
                'name' => sprintf('Guzzle: %s', $request->getUri()->getHost()),
                'attributes' => [
                        'http.method' => $request->getMethod(),
                        'http.uri' => (string)$request->getUri(),
                    ] + $attrHeaders,
                'kind' => Span::KIND_CLIENT,
                'sameProcessAsParentSpan' => !empty($this->spans),
            ]);
            $scope = $this->tracer->withSpan($span);

            /** @var PromiseInterface $promise */
            $promise = $handler($request, $options);

            return $promise->then(
                static function (Response $response) use ($span, $scope) {
                    $statusCode = $response->getStatusCode();
                    $span->addAttribute('http.status_code', (string)$statusCode);

                    // If it's an error, annotate it as such
                    if ($statusCode >= 400) {
                        $span->addAttribute('error', 'true');
                    }

                    if ($this->logBody) {
                        $bodyLength = (int)$response->getHeaderLine('Content-Length');
                        // Jaeger agent limits us to 65K bytes per request, so in order to "balance" the data going to the
                        // collector, we don't send any information bigger than 4K bytes per Span, as we can have many spans
                        // within the trace. With this, we will have some response bodies in Jaeger, but the really big ones
                        // will be skipped.
                        // PHP Jaeger client: https://github.com/dz0ny/opencensus-php-exporter-jaeger/blob/ebecdf9769b4199047752f0bc2dbdeb0e514fec4/src/Jaeger/UDPClient.php#L92
                        // Go Jaeger agent const size: https://github.com/jaegertracing/jaeger-client-go/blob/f7e0d4744fa6d5287c53b8ac8d4f83089ce07ce8/utils/udp_client.go#L31
                        // Go Jaeger agent: https://github.com/jaegertracing/jaeger-client-go/blob/f7e0d4744fa6d5287c53b8ac8d4f83089ce07ce8/utils/udp_client.go#L87-L90
                        if ($bodyLength > 0 && $bodyLength <= 4096) {
                            $body = (string)$response->getBody();
                        } else {
                            $body = 'Either Content-Length is missing, or it is bigger than 4096';
                        }
                        $span->addAttribute('response.body', $body);
                    }

                    $attrHeaders = [];
                    foreach ($response->getHeaders() as $name => $values) {
                        $attrHeaders['response.' . $name] = implode(', ', $values);
                    }
                    $span->addAttributes($attrHeaders);
                    $scope->close();

                    return new FulfilledPromise($response);
                },
                static function (GuzzleException $r) use ($span, $scope) {
                    $span->addAttribute('error', 'true');
                    $span->addAttribute('exception', sprintf('%s: %s', get_class($r), $r->getMessage()));
                    $scope->close();

                    return new RejectedPromise($r);
                }
            );
        };
    }
}

