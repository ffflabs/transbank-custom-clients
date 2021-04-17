<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace Tests\Feature;

use CTOhm\TransbankCustomClients\AddRequestHeaderMiddleware;
use CTOhm\TransbankCustomClients\ClientLogMiddleware;
use Http\Adapter\Guzzle7\Client as Guzzle7Client;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface;

it('can send a request with guzzle adapter client', function () {
    $client = new Guzzle7Client();

    $messageFactory = Psr17FactoryDiscovery::findRequestFactory();

    $homeResponse = $client->sendRequest(
        $messageFactory->createRequest('GET', 'http://httplug.io')
    );

    expect($homeResponse->getStatusCode())->toBe(200);

    return $client;
});

it(
    'can inject both a logging middleware and the addHeader middleware into a guzzle adapter client',
    function (ClientInterface $client) {
        $messageFactory = Psr17FactoryDiscovery::findRequestFactory();

        $addHeader = new AddRequestHeaderMiddleware('x-random-token', __FILE__);
        $log = $this->getLogger();

        $loggerMiddleware = new ClientLogMiddleware($log);

        $request = $messageFactory->createRequest('GET', 'http://httplug.io');
        $handler = function () use ($request, $client) {
            return $client->sendRequest($request);
        };

        foreach ([$loggerMiddleware, $addHeader] as $middleware) {
            $handler = $middleware($handler);
        }
        $response = $handler($request, []);

        expect($log)->toHaveTestRecords();
        $loggedRequest = $log->getHandlers()[0]->getRecords()[0];

        expect($loggedRequest)->toBeArray()->toHaveKey('context');
        expect($loggedRequest['context'])->toBeArray()->toHaveKey('headers');
        expect($loggedRequest['context']['headers'])->toBeArray()->toHaveKey('x-random-token');
        expect($response->getStatusCode())->toBe(200);
    }
)->depends('it can send a request with guzzle adapter client');
