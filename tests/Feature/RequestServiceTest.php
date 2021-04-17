<?php

/**
 * CTOhm - Transbank Custom Clients
 */

use CTOhm\TransbankCustomClients\ClientLogMiddleware;
use CTOhm\TransbankCustomClients\MiddlewareAwareClient;
use CTOhm\TransbankCustomClients\MiddlewareAwareClientService;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Transbank\Webpay\Options;

/** @ test */
it('send_the_headers_provided_by_the_given_options', function () {
    $expectedHeaders = ['api_key' => 'commerce_code', 'api_secret' => 'fakeApiKey'];

    $optionsMock = $this->createMock(Options::class);
    $optionsMock
        ->expects($this->once())
        ->method('getHeaders')
        ->willReturn($expectedHeaders);

    $httpClientMock = $this->createMock(MiddlewareAwareClient::class);
    $httpClientMock
        ->expects($this->once())
        ->method('request')
        ->with($this->anything(), $this->anything(), $this->anything(), $this->equalTo([
            'headers' => $expectedHeaders,
        ]))
        ->willReturn(
            new Response(200, [], \json_encode([
                'token' => __METHOD__,
                'url' => 'http://mock.cl/',
            ]))
        );

    $request = (new MiddlewareAwareClientService($httpClientMock))->request('POST', '/transactions', [], $optionsMock);
});

/** @test */
it('uses_the_base_url_provided_by_the_given_options', function () {
    $expectedBaseUrl = 'http://mock.cl/';
    $endpoint = '/transactions';

    $optionsMock = $this->createMock(Options::class);
    $optionsMock
        ->expects($this->once())
        ->method('getApiBaseUrl')
        ->willReturn($expectedBaseUrl);

    $log = new Logger('WebpayPlus');
    $streamHandler = new StreamHandler('php://stderr', Logger::DEBUG);
    $testHandler = new TestHandler();
    $lineFormatter = new LineFormatter(null, null, true, true);
    $lineFormatter->setJsonPrettyPrint(true);
    $streamHandler->setFormatter($lineFormatter);
    $log->pushHandler($testHandler); // <<< uses a stream
    $log->pushHandler($streamHandler); // <<< uses a stream

    $request = (new MiddlewareAwareClientService(null))
        ->withMockResponses([
            new Response(200, ['Content-Type' => 'application/json'], \json_encode(['token' => \uniqid(), 'url' => 'http://request0.cl/'])),
        ])->withHandlerStack(function (HandlerStack $handlerStack) use ($log) {
            $handlerStack->push(new ClientLogMiddleware($log), 'logger');
        })->request('POST', $endpoint, [], $optionsMock);

    expect($testHandler->getRecords())->toBeArray()->toHaveCount(2);
});
