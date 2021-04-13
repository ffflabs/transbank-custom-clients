<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Transbank\Webpay\Options;

/**
 * @internal
 * @coversNothing
 */
class WebpayPlusTestCase extends TestCase
{
    /**
     * @var int
     */
    protected $amount;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $buyOrder;

    /**
     * @var string
     */
    protected $returnUrl;

    /**
     * @var string
     */
    protected $mockBaseUrl;

    /**
     * @var Options|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $optionsMock;

    /**
     * @var array
     */
    protected $headersMock;

    protected function setUp(): void
    {
        $this->amount = 1000;
        $this->sessionId = 'some_session_id_' . \uniqid();
        $this->buyOrder = '123999555';
        $this->returnUrl = 'https://comercio.cl/callbacks/transaccion_finalizada';
        $this->mockBaseUrl = 'http://mockurl.cl';
    }
}
