<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use Closure;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Transbank\Contracts\RequestService;
use Transbank\Webpay\Exceptions\TransbankApiRequest;
use Transbank\Webpay\Exceptions\WebpayRequestException;
use Transbank\Webpay\Options;

class Psr7RequestService implements RequestService
{
    use Tappable;
    /**
     * @var ClientInterface
     */
    protected $httpClient;

    private array $middlewares = [];

    public function __construct(?ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
    }

    public function tap(Closure $callback)
    {
        $callback($this);

        return $this;
    }

    public function addMiddleware(ClientMiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param $method
     * @param $endpoint
     * @param $payload
     *
     * @throws GuzzleException
     * @throws WebpayRequestException
     *
     * @return array
     */
    public function request(
        $method,
        $endpoint,
        $payload,
        Options $options
    ) {
        $baseUrl = $options->getApiBaseUrl();
        $headers = $options->getHeaders();
        $messageFactory = Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $tbkrequest = new TransbankApiRequest($method, $baseUrl, $endpoint, $payload, $headers);

        if (!$payload) {
            $payload = null;
        }

        if (\is_array($payload)) {
            $payload = \json_encode($payload);
        }

        /*$messageFactory->createRequest($method, $baseUrl . $endpoint);
        if ($method !== 'GET') {
            $req =    $req->withBody($streamFactory->createStream($payload));
        }
        foreach ($options->getHeaders() as $headerName => $headerValue) {
            $req = $req->withHeader($headerName, $headerValue);
        }*/
        $request = new Request($method, $baseUrl . $endpoint, $headers ?? [], $payload);

        $handler = function () use ($request) {
            return $this->httpClient->sendRequest($request);
        };

        foreach (\array_reverse($this->middlewares) as $middleware) {
            $handler = $middleware($handler);
        }
        $response = $handler($request, []);

        //dd($response);
        $responseStatusCode = $response->getStatusCode();

        if (!\in_array($responseStatusCode, [200, 204, 201], true)) {
            $reason = $response->getReasonPhrase();
            $message = "Could not obtain a response from Transbank API: {$reason} (HTTP code {$responseStatusCode})";
            $body = \json_decode($response->getBody(), true);
            $tbkErrorMessage = null;

            if (isset($body['error_message'])) {
                $tbkErrorMessage = $body['error_message'];
                $message = "Transbank API REST Error: {$tbkErrorMessage} | {$message}";
            }

            throw new WebpayRequestException($message, $tbkErrorMessage, $responseStatusCode, $tbkrequest);
        }

        return \json_decode($response->getBody(), true);
    }
}
