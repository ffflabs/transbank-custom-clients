<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace Tests\Feature;

use Http\Client\Curl\Client;
use Http\Discovery\Psr17FactoryDiscovery;

it('can send a request with curl client', function () {
    $responseFactory = Psr17FactoryDiscovery::findResponseFactory();
    $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    $client = new Client($responseFactory, $streamFactory);

    $messageFactory = Psr17FactoryDiscovery::findRequestFactory();

    $homeResponse = $client->sendRequest(
        $messageFactory->createRequest('GET', 'http://httplug.io')
    );

    expect($homeResponse->getStatusCode())->toBe(200);

    $missingPageResponse = $client->sendRequest(
        $messageFactory->createRequest('GET', 'http://httplug.io/missingPage')
    );
    expect($missingPageResponse->getStatusCode())->toBe(404);
});
