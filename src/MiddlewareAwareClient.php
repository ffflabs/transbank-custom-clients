<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use Closure;
use Composer\InstalledVersions;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Transbank\Contracts\HttpClientInterface;
use Transbank\Utils\HttpClient;

class MiddlewareAwareClient extends HttpClient implements HttpClientInterface
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

    public function withHandlerStack(?callable $callback = null): self
    {
        $installedVersion = 'unknown';

        try {
            $installedVersion = InstalledVersions::getVersion('transbank/transbank-sdk');
        } catch (\Exception $exception) {
        }
        $handlerStack = HandlerStack::create($this->handler);

        if ($callback) {
            $callback($handlerStack);
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

    /**
     * Undocumented function.
     *
     * @return static
     */
    public function build(): self
    {
        return $this->withHandlerStack(function (HandlerStack $handlerStack) {
            foreach ($this->middlewares as $name => $middleware) {
                $handlerStack->push($middleware, $name);
            }
        });
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
    }
}
