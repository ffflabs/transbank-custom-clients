<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ClientLogMiddleware extends ClientMiddleware implements ClientMiddlewareInterface
{
    use Tappable;
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @return \Closure(\Psr\Http\Message\RequestInterface, array):\Closure(\Psr\Http\Message\RequestInterface, array):mixed
     */
    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options = []) use ($handler) {
            $this->logger->info(
                \sprintf('%s %s (%s)' . \PHP_EOL, $request->getMethod(), $request->getRequestTarget(), $request->getUri()->__toString()),
                self::normalizeRequestPayload($request)
            );

            $res = $handler($request, $options);

            return ($res instanceof PromiseInterface) ? $res->then(
                fn (ResponseInterface $response) => tap($response, fn ($response) => $this->logger->info(
                    \sprintf('Response from %s %s ' . \PHP_EOL, $request->getRequestTarget(), $response->getReasonPhrase()),
                    self::normalizeResponsePayload($response)
                ))
            ) : tap($res, fn ($response) => $this->logger->info(
                \sprintf('Response from %s %s ' . \PHP_EOL, $request->getRequestTarget(), $response->getReasonPhrase()),
                self::normalizeResponsePayload($response)
            ));
        };
    }
}
