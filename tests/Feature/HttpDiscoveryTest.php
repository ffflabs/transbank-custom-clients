<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace Tests\Feature;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;

it('can discover a compatible http client implementation', function () {
    $client = HttpClientDiscovery::find();
    expect($client)->toBeInstanceOf(HttpClient::class);

    return $client;
});

it('can discover a compatible http message factory implementation', function (HttpClient $client) {
    $messageFactory = MessageFactoryDiscovery::find();

    expect($messageFactory)->toBeInstanceOf(MessageFactory::class);

    $homeResponse = $client->sendRequest(
        $messageFactory->createRequest('GET', 'http://httplug.io')
    );

    expect($homeResponse->getStatusCode())->toBe(200);

    $missingPageResponse = $client->sendRequest(
        $messageFactory->createRequest('GET', 'http://httplug.io/missingPage')
    );
    expect($missingPageResponse->getStatusCode())->toBe(404);
})->depends('it can discover a compatible http client implementation');
