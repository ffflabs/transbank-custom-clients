<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace Tests\Feature;

use CTOhm\TransbankCustomClients\ClientLogMiddleware;
use CTOhm\TransbankCustomClients\MiddlewareAwareClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Transbank\Utils\HttpClientRequestService;
use Transbank\Webpay\WebpayPlus\Transaction;

/**
 * @test
 * @group custom_client
 */
it('can inject a psr logger into a middleware aware client', function () {
    // create a log channel
    $log = new Logger('WebpayPlus');
    $streamHandler = new StreamHandler('php://stderr', Logger::DEBUG);
    $testHandler = new TestHandler();
    $lineFormatter = new LineFormatter(null, null, true, true);
    $lineFormatter->setJsonPrettyPrint(true);
    $streamHandler->setFormatter($lineFormatter);
    $log->pushHandler($testHandler); // <<< uses a stream
    $log->pushHandler($streamHandler); // <<< uses a stream

    $httpClient = (new MiddlewareAwareClient())
        ->withMockResponses([
            new Response(200, ['Content-Type' => 'application/json'], \json_encode(['token' => \uniqid(), 'url' => 'http://request0.cl/'])),
        ])->withHandlerStack(function (HandlerStack $handlerStack) use ($log) {
            $handlerStack->push(new ClientLogMiddleware($log), 'logger');
        });

    $httpClientRequestService = new HttpClientRequestService($httpClient);

    $transaction = (new Transaction(null, $httpClientRequestService));
    $this->assertSame($transaction->getRequestService(), $httpClientRequestService);
    $transaction->create(\sprintf('%s1', $this->buyOrder), $this->sessionId, $this->amount, $this->returnUrl);
    expect($testHandler->getRecords())->toBeArray()->toHaveCount(2);
});
