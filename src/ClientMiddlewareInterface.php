<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use Closure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

interface ClientMiddlewareInterface
{


    /**
     * @return \Closure(\Psr\Http\Message\RequestInterface, array):\Closure(\Psr\Http\Message\RequestInterface, array):mixed
     */
    public function __invoke(callable $handler): Closure;
}
