<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use ArrayIterator;
use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;

class ClientHistoryMiddleware extends ClientMiddleware
{
    private array $history;

    public function __construct()
    {
        $this->history = [];
    }

    /**
     * @return \Closure(\Psr\Http\Message\RequestInterface, array):\Closure(\Psr\Http\Message\RequestInterface, array):mixed
     */
    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $handler($request, $options)
                ->then(
                    function ($value) use ($request, $options) {
                        $this->history[] = [
                            'request' => $request,
                            'response' => $value,
                            'error' => null,
                            'options' => $options,
                        ];

                        return $value;
                    }
                )->otherWise(
                    function ($reason) use ($request, $options) {
                        $this->history[] = [
                            'request' => $request,
                            'response' => null,
                            'error' => $reason,
                            'options' => $options,
                        ];

                        return $reason instanceof PromiseInterface ? $reason : new RejectedPromise($reason);
                    }
                );
        };
    }

    public function getHistory(): ArrayIterator
    {
        return new \ArrayIterator($this->history);
    }
}
