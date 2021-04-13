<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use Closure;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Transbank\Contracts\RequestService;
use Transbank\Utils\HttpClientRequestService;

class MiddlewareAwareClientService extends HttpClientRequestService implements RequestService
{
    /**
     * @var MiddlewareAwareClient
     */
    protected $httpClient;

    private ?ClientInterface $client = null;

    private array $history = [];

    private $handler;

    private array $middlewares = [];

    public function __construct(?MiddlewareAwareClient $httpClient = null)
    {
        $this->setHttpClient(null !== $httpClient ? $httpClient : new MiddlewareAwareClient());
    }

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
        return $this->tap(fn () => $this->httpClient->withMockResponses($responses));
    }

    public function withHandlerStack(?callable $callback = null): self
    {
        return $this->tap(fn () => $this->httpClient->withHandlerStack($callback));
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
}
