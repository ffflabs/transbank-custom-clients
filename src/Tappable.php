<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use Closure;

trait Tappable
{
    public function tap(Closure $callback)
    {
        return tap($this, $callback);
    }
}
