<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace Tests\Feature;

use CTOhm\TransbankCustomClients\AddHeaderMiddleware;
use CTOhm\TransbankCustomClients\ClientLogMiddleware;
use Http\Client\Curl\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

it('can send a request with curl client', function () {
    $responseFactory = Psr17FactoryDiscovery::findResponseFactory();
    $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    $client = new Client($responseFactory, $streamFactory);

    $messageFactory = Psr17FactoryDiscovery::findRequestFactory();


    $homeResponse = $client->sendRequest(
        $messageFactory->createRequest('GET', 'http://httplug.io')
    );

    expect($homeResponse->getStatusCode())->toBe(200);


    return $client;
});


it('can send a request with middleware for curl client', function ($client) {


    $messageFactory = Psr17FactoryDiscovery::findRequestFactory();

    $request = $messageFactory->createRequest('GET', 'http://httplug.io');
    $addHeader = new AddHeaderMiddleware('x-access-token', __FILE__);
    $log = $this->getLogger();

    $loggerMiddleware = new ClientLogMiddleware($log);


    $handler = function () use ($request, $client) {
        return $client->sendRequest($request);
    };
    $prev = $handler;
    foreach ([$loggerMiddleware, $addHeader] as $middleware) {
        $prev = $middleware($prev);
    }

    $homeResponse = $prev($request, []); //  $addHeader($loggerMiddleware(        $handler    ))($request, []);

    expect($log->getHandlers()[0]->getRecords())->not()->toBeEmpty();
    $loggedRequest = $log->getHandlers()[0]->getRecords()[0];

    expect($loggedRequest)->toBeArray()->toHaveKey('context');
    expect($loggedRequest['context'])->toBeArray()->toHaveKey('headers');
    expect($loggedRequest['context']['headers'])->toBeArray()->toHaveKey('x-access-token');
    // expect($loggedRequest->getHeaderLine('x-access-token'))->toBeString();
    expect($homeResponse->getStatusCode())->toBe(200);
})->depends('it can send a request with curl client');
