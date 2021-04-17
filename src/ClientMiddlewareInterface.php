<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use Closure;

interface ClientMiddlewareInterface
{
    /**
     * @return \Closure(\Psr\Http\Message\RequestInterface, array):\Closure(\Psr\Http\Message\RequestInterface, array):mixed
     */
    public function __invoke(callable $handler): Closure;
}
