<?php

/**
 * CTOhm - Transbank Custom Clients
 */

use CTOhm\TransbankCustomClients\ClientLogMiddleware;
use CTOhm\TransbankCustomClients\MiddlewareAwareClient;
use CTOhm\TransbankCustomClients\MiddlewareRequestService;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Transbank\Webpay\Options;

/** @ test */
it('can fire a request using a mocked client and a custom requestService', function () {
    $expectedHeaders = ['api_key' => 'commerce_code', 'api_secret' => 'fakeApiKey'];
    $expectedResponsePayload = [
        'token' => __METHOD__,
        'url' => 'http://mock.cl/',
    ];
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
            new Response(200, [], \json_encode($expectedResponsePayload))
        );

    $responsePayload = (new MiddlewareRequestService($httpClientMock))->request('POST', '/transactions', [], $optionsMock);
    expect($responsePayload)->toEqualCanonicalizing($expectedResponsePayload);
});

/** @test */
it('inject a logging middleware into a middleware aware client service', function () {
    $expectedBaseUrl = 'http://mock.cl/';
    $endpoint = '/transactions';

    $optionsMock = $this->createMock(Options::class);
    $optionsMock
        ->expects($this->once())
        ->method('getApiBaseUrl')
        ->willReturn($expectedBaseUrl);

    $log = $this->getLogger('WebpayPlus');
    $streamHandler = $this->getLogConsoleHandler();
    $log->pushHandler($streamHandler); // <<< uses a stream

    $request = (new MiddlewareRequestService(null))
        ->withMockResponses([
            new Response(200, ['Content-Type' => 'application/json'], \json_encode(['token' => \uniqid(), 'url' => 'http://request0.cl/'])),
        ])->withHandlerStack(function (HandlerStack $handlerStack) use ($log) {
            $handlerStack->push(new ClientLogMiddleware($log), 'logger');
        })->request('POST', $endpoint, [], $optionsMock);

    expect($log)->toHaveTestRecords(2);
});
