<?php

/**
 * CTOhm - Transbank Custom Clients
 */

namespace CTOhm\TransbankCustomClients;

use Closure;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientMiddleware
{
    public function tap(Closure $callback)
    {
        $callback($this);

        return $this;
    }

    public static function normalizeMessage(MessageInterface $message)
    {
        $headers = \array_map(
            fn ($headerValues) => \implode(', ', $headerValues),
            \array_filter(
                $message->getHeaders(),
                fn ($key) => 'Cookies' !== $key,
                \ARRAY_FILTER_USE_KEY
            )
        );
        $body = $message->getBody()->getContents();

        return [
            'headers' => $headers, 'body' => 'application/json' === ($headers['Content-Type'] ?? null) ? \json_decode($body, true) : $body,
        ];
    }

    public static function normalizeResponsePayload(ResponseInterface $response)
    {
        return \array_merge([
            'reason' => $response->getReasonPhrase(),
            'status' => $response->getStatusCode(),
        ], self::normalizeMessage($response));
    }

    public static function normalizeRequestPayload(RequestInterface $request)
    {
        return \array_merge(self::normalizeMessage($request), [
            'method' => $request->getMethod(),
            'target' => $request->getRequestTarget(),
        ]);
    }
}
