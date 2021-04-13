<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace Tests\Feature;

use CTOhm\TransbankCustomClients\ClientHistoryMiddleware;
use CTOhm\TransbankCustomClients\MiddlewareAwareClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Transbank\Utils\HttpClientRequestService;
use Transbank\Webpay\WebpayPlus\Transaction;

/**
 * @test
 * @group custom_client
 */
it('can record requests history on a middleware aware client', function () {
    $historyMiddleware = new ClientHistoryMiddleware();

    $httpClient = (new MiddlewareAwareClient())
        ->withMockResponses([
            new Response(200, ['Content-Type' => 'application/json'], \json_encode(['token' => \uniqid(), 'url' => 'http://request1.cl/'])),
            new Response(200, ['Content-Type' => 'application/json'], \json_encode(['token' => \uniqid(), 'url' => 'http://request2.cl/'])),
        ])->withHandlerStack(function (HandlerStack $handlerStack) use ($historyMiddleware) {
            $handlerStack->push($historyMiddleware, 'history');
        });
    $httpClientRequestService = new HttpClientRequestService($httpClient);

    try {
        $transaction = (new Transaction(null, $httpClientRequestService));
        $this->assertSame($transaction->getRequestService(), $httpClientRequestService);
        $transaction->create(\sprintf('%s1', $this->buyOrder), $this->sessionId, $this->amount, $this->returnUrl);

        $transaction2 = (new Transaction(null, $httpClientRequestService));
        $this->assertSame($transaction2->getRequestService(), $httpClientRequestService);
        $transaction2->create(\sprintf('%s2', $this->buyOrder), $this->sessionId, $this->amount, $this->returnUrl);
    } finally {
        // Count the number of transactions

        //> 2

        // Iterate over the requests and responses
        foreach ($historyMiddleware->getHistory() as $transaction) {
            echo \sprintf('%s %s %s', \PHP_EOL, $transaction['request']->getMethod(), $transaction['request']->getUri());

            if ($transaction['response']) {
                echo \sprintf(' %s ( %s ) %s %s', \PHP_EOL, $transaction['response']->getStatusCode(), $transaction['response']->getBody(), \PHP_EOL);
            } elseif ($transaction['error']) {
                echo $transaction['error'];
            }
        }
    }
});
