<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use Closure;
use Psr\Http\Message\RequestInterface;

class AddRequestHeaderMiddleware extends ClientMiddleware implements ClientMiddlewareInterface
{
    use Tappable;
    private string $headerName;

    private string $headerValue;

    public function __construct(string $headerName, string $headerValue)
    {
        $this->headerName = $headerName;
        $this->headerValue = $headerValue;
    }

    /**
     * @return \Closure(\Psr\Http\Message\RequestInterface, array):\Closure(\Psr\Http\Message\RequestInterface, array):mixed
     */
    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $modifiedRequest = $request->withAddedHeader($this->headerName, $this->headerValue);

            return $handler($modifiedRequest, $options);
        };
    }
}
