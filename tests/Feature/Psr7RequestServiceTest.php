<?php

/**
 * CTOhm - Transbank Custom Clients
 */

use CTOhm\TransbankCustomClients\ClientLogMiddleware;
use CTOhm\TransbankCustomClients\Psr7RequestService;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use Transbank\Webpay\WebpayPlus\Transaction;

it('can send a transaction request with guzzle adapter client', function () {
    global $argv, $argc;
    dump(\getenv('APP_USER_ID'));
    dd($argv);
    $httpClient = new Client();
    $httpClient->setDefaultResponse(new Response(201, ['Content-Type' => 'application/json'], \json_encode([
        'token' => \sprintf('%s.%s', $this->getName(), \uniqid()),
        'url' => 'http://mock.cl/',
    ])));

    $log = $this->getLogger('Psr7RequestService');

    $httpClientRequestService = tap(
        new Psr7RequestService($httpClient),
        fn ($reqService) => $reqService->addMiddleware(new ClientLogMiddleware($log))
    );

    $transaction = (new Transaction(null, $httpClientRequestService));

    try {
        $transaction->create(\sprintf('%s1', $this->buyOrder), $this->sessionId, $this->amount, $this->returnUrl);
    } catch (\Exception $e) {
        $this->fail($e->getMessage());
        dump(normalizeException($e));
    }
    expect($log->getHandlers()[0]->getRecords())->toBeArray()->toHaveCount(2);
});
