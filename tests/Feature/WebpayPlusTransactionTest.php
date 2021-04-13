<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace Tests\Feature;

use CTOhm\TransbankCustomClients\ClientHistoryMiddleware;
use CTOhm\TransbankCustomClients\ClientLogMiddleware;
use CTOhm\TransbankCustomClients\LoggableClient;
use CTOhm\TransbankCustomClients\MiddlewareAwareClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Transbank\Utils\HttpClientRequestService;
use Transbank\Webpay\WebpayPlus\Transaction;

/**
 * @test
 * @group custom_client
 */
it('Logs requests to console when a psr log is injected', function () {
    // create a log channel
    $log = new Logger('WebpayPlus');
    $streamHandler = new StreamHandler('php://stderr', Logger::DEBUG);
    $lineFormatter = new LineFormatter(null, null, true, true);
    $lineFormatter->setJsonPrettyPrint(true);
    $streamHandler->setFormatter($lineFormatter);
    $log->pushHandler($streamHandler); // <<< uses a stream

    $httpClient = (new LoggableClient())
        ->withHistory()
        ->withLogger($log)
        //->withRequestsDebugger()
        //->withTapMiddleware()
        ->withMockResponses([
            new Response(200, ['Content-Type' => 'application/json'], \json_encode(['token' => \uniqid(), 'url' => 'http://mock.cl/'])),
        ])->build();
    $httpClientRequestService = new HttpClientRequestService($httpClient);

    try {
        $transaction = (new Transaction(null, $httpClientRequestService));
        $this->assertSame($transaction->getRequestService(), $httpClientRequestService);
        $transaction->create($this->buyOrder, $this->sessionId, $this->amount, $this->returnUrl);
    } finally {
        foreach ($httpClient->getHistory() as $transaction) {
            echo \sprintf('%s %s %s', \PHP_EOL, $transaction['request']->getMethod(), $transaction['request']->getUri());

            if ($transaction['response']) {
                echo \sprintf(' %s ( %s ) %s', \PHP_EOL, $transaction['response']->getStatusCode(), $transaction['response']->getBody());
            } elseif ($transaction['error']) {
                echo $transaction['error'];
            }
        }
    }
});

/**
 * @test
 * @group custom_client
 */
it('can record request history on a custom client', function () {
    $httpClient = (new LoggableClient())
        ->withHistory()
        /*->withMockResponses([
                new Response(200, ['Content-Type' => 'application/json'], json_encode(['token' => uniqid(), 'url' => 'http://request1.cl/',])),
                new Response(200, ['Content-Type' => 'application/json'], json_encode(['token' => uniqid(), 'url' => 'http://request2.cl/',]))
            ])
         */
        ->build();
    $httpClientRequestService = new HttpClientRequestService($httpClient);

    try {
        $transaction = (new Transaction(null, $httpClientRequestService));
        $this->assertSame($transaction->getRequestService(), $httpClientRequestService);
        $transaction->create(\sprintf('%s1', $this->buyOrder), $this->sessionId, $this->amount, $this->returnUrl);

        $transaction2 = (new Transaction(null, $httpClientRequestService));
        $this->assertSame($transaction2->getRequestService(), $httpClientRequestService);
        $transaction2->create(\sprintf('%s2', $this->buyOrder), $this->sessionId, $this->amount, $this->returnUrl);
    } finally {
        foreach ($httpClient->getHistory() as $transaction) {
            echo \sprintf('%s %s %s', \PHP_EOL, $transaction['request']->getMethod(), $transaction['request']->getUri());

            if ($transaction['response']) {
                echo \sprintf(' %s ( %s ) %s', \PHP_EOL, $transaction['response']->getStatusCode(), $transaction['response']->getBody());
            } elseif ($transaction['error']) {
                echo $transaction['error'];
            }
        }
    }
});
/**
 * @test
 * @group custom_client
 */
it('can create a middleware aware client', function () {
    // create a log channel
    $log = new Logger('name');
    $log->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG)); // <<< uses a stream

    // add records to the log

    $log->warning('Foo');
    $log->error('Bar');

    $historyMiddleware = new ClientHistoryMiddleware();

    $httpClient = (new MiddlewareAwareClient())
        ->withMockResponses([
            new Response(200, ['Content-Type' => 'application/json'], \json_encode(['token' => \uniqid(), 'url' => 'http://request1.cl/'])),
            new Response(200, ['Content-Type' => 'application/json'], \json_encode(['token' => \uniqid(), 'url' => 'http://request2.cl/'])),
        ])->withHandlerStack(function (HandlerStack $handlerStack) use ($log, $historyMiddleware) {
            $handlerStack->push($historyMiddleware, 'history');
            $handlerStack->push(new ClientLogMiddleware($log), 'logger');
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
                echo \sprintf(' %s ( %s ) %s', \PHP_EOL, $transaction['response']->getStatusCode(), $transaction['response']->getBody());
            } elseif ($transaction['error']) {
                echo $transaction['error'];
            }
        }
    }
});
