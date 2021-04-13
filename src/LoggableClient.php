<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use ArrayIterator;
use Closure;
use Composer\InstalledVersions;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Transbank\Contracts\HttpClientInterface;
use Transbank\Utils\HttpClient;

class LoggableClient extends HttpClient implements HttpClientInterface
{
    private ?ClientInterface $client = null;

    private array $history = [];

    private $handler;

    private array $middlewares = [];

    public function tap(Closure $callback)
    {
        $callback($this);

        return $this;
    }

    /**
     * Undocumented function.
     *
     * @param Response[] $responses
     *
     * @return static
     */
    public function withMockResponses(array $responses): self
    {
        return $this->tap(fn () => $this->handler = new MockHandler($responses));
    }

    public function withHistory(): self
    {
        return $this->tap(function () {
            $this->middlewares['history'] = new ClientHistoryMiddleware();
        });
    }

    public function getHistory(): ArrayIterator
    {
        return $this->middlewares['history']->getHistory();
    }

    /**
     * Undocumented function.
     *
     * @return static
     */
    public function build(): self
    {
        $installedVersion = 'unknown';

        try {
            $installedVersion = InstalledVersions::getVersion('transbank/transbank-sdk');
        } catch (\Exception $exception) {
        }
        $handlerStack = HandlerStack::create($this->handler);

        foreach ($this->middlewares as $name => $middleware) {
            $handlerStack->push($middleware, $name);
        }

        return $this->tap(fn () => $this->client = new Client([
            'decode_content' => true,
            'handler' => $handlerStack, 'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'SDK-PHP/' . $installedVersion,
            ],
        ]));
    }

    public function withTapMiddleware(): self
    {
        return $this->tap(
            fn () => $this->middlewares['tap'] = Middleware::tap(
                fn (RequestInterface $requestBefore) => dump(self::normalizeRequestPayload($requestBefore)),
                fn (RequestInterface $requestAfter, array $options, $something) => dump(\get_class($something), self::normalizeRequestPayload($requestAfter)),
            )
        );
    }

    public function withLogger(?LoggerInterface $logger = null)
    {
        return $this->tap(fn () => $this->middlewares['request_logger'] = new ClientLogMiddleware($logger));
    }

    /**
     * Undocumented function.
     */
    public function withRequestsDebugger(): self
    {
        $requestDebugger = function (callable $handler): Closure {
            return function (RequestInterface $request, array $options = []) use ($handler) {
                dump(self::normalizeRequestPayload($request));

                return $handler($request, $options);
            };
        };

        return $this->tap(fn () => $this->middlewares['request_debugger'] = $requestDebugger);
    }

    public static function normalizeMessage(MessageInterface $message)
    {
        $headers = \array_map(
            fn ($headerValues) => \implode(', ', $headerValues),
            \array_filter(
                $message->getHeaders(),
                fn ($key) => 'Cookies' !== $key,
                \ARRAY_FILTER_USE_KEY
            )
        );
        $body = $message->getBody()->getContents();

        return [
            'headers' => $headers, 'body' => 'application/json' === ($headers['Content-Type'] ?? null) ? \json_decode($body, true) : $body,
        ];
    }

    public static function normalizeResponsePayload(ResponseInterface $response)
    {
        return \array_merge([
            'reason' => $response->getReasonPhrase(),
            'status' => $response->getStatusCode(),
        ], self::normalizeMessage($response));
    }

    public static function normalizeRequestPayload(RequestInterface $request)
    {
        return \array_merge(self::normalizeMessage($request), [
            'method' => $request->getMethod(),
            'target' => $request->getRequestTarget(),
        ]);
    }

    /**
     * @param $method
     * @param $url
     * @param $options
     * @param $payload
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function request($method, $url, $payload = [], $options = null)
    {
        if (!$payload) {
            $payload = null;
        }

        if (\is_array($payload)) {
            $payload = \json_encode($payload);
        }

        $request = new Request($method, $url, $options['headers'] ?? [], $payload);

        return $this->client->sendRequest($request);
        /*->send($request, [
            'on_stats' => function (TransferStats $stats) use ($request) {

                dump([
                    //'request' => self::normalizeRequestPayload($request),
                    //'response' => $stats->hasResponse() ? self::normalizeResponsePayload($stats->getResponse()) : [],
                    'errors' => $stats->getHandlerErrorData(),
                    'effectiveURI' => $stats->getEffectiveUri()->__toString(),
                    'transferTime' =>  $stats->getTransferTime()
                ]);
            }
        ]);*/
    }
}
