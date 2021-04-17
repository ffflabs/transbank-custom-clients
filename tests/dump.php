<?php

/**
 * CTOhm - Transbank Custom Clients
 */

use Kint\Kint;
use Monolog\Utils;
use Psr\Http\Message\RequestInterface;

if (!\function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function tap($value, ?callable $callback = null)
    {
        if (null !== $callback) {
            $callback($value);
        }

        return $value;
    }
}

if (!\function_exists('addResponseHeader')) {
    function addResponseHeader($header, $value)
    {
        return function (callable $handler) use ($header, $value) {
            return function (
                RequestInterface $request,
                array $options
            ) use (
                $handler,
                $header,
                $value
            ) {
                $response = $handler($request, $options);

                return $response->withHeader($header, $value);
            };
        };
    }
}

if (!\function_exists('normalize')) {
    /**
     * Normalizes given $data.
     *
     * @param mixed $data
     * @param mixed $depth
     *
     * @return mixed
     */
    function normalize($data, $depth = 0)
    {
        if (10 < $depth) {
            return 'Over 9 levels deep, aborting normalization';
        }

        if (\is_array($data) || $data instanceof \Traversable) {
            $normalized = [];

            $count = 1;
            $total = null;

            if (\is_countable($data)) {
                $total = \count($data);
            }

            foreach ($data as $key => $value) {
                if (25 < $count++) {
                    $normalized['...'] = \sprintf('Over %d items (%s total), aborting normalization', 25, $total ?? 'undetermined');

                    break;
                }

                $normalized[$key] = normalize($value, $depth + 1);
            }

            return $normalized;
        }

        if ($data instanceof Exception || $data instanceof Throwable) {
            return normalizeException($data, $depth);
        }
        //kdump($data);
        return $data;
    }
}

if (!\function_exists('normalizeException')) {
    /**
     * Normalizes given exception with or without its own stack trace based on
     * `includeStacktraces` property.
     *
     * @param Exception|Throwable $e
     */
    function normalizeException(Throwable $e, int $depth = 1, bool $includeStacktraces = true): array
    {
        $class = Utils::getClass($e);
        $base_path = \dirname(__DIR__);
        $data = [
            'class' => $class,
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => \str_replace($base_path, '', $e->getFile()) . ':' . $e->getLine(),
        ];

        $maxTraceLength = 30;

        if ($includeStacktraces) {
            $trace = $e->getTrace();

            foreach ($trace as $index => $frame) {
                if ($index > $maxTraceLength) {
                    break;
                }

                if (isset($frame['file'])) {
                    $data['trace'][] = \str_replace($base_path, '', $frame['file']) . ':' . $frame['line'];
                } elseif (isset($frame['function']) && '{closure}' === $frame['function']) {
                    // We should again normalize the frames, because it might contain invalid items
                    $data['trace'][] = $frame['function'];
                } elseif (\is_string($frame)) {
                    $data['trace'][] = \str_replace($base_path, '', $frame);
                } else {
                    // We should again normalize the frames, because it might contain invalid items
                    $frame = normalize($frame, $depth);
                    $data['trace'][] = $frame;
                }
            }
        }

        if (10 <= $depth) {
            return $data;
        }
        $previous = $e->getPrevious();

        if ($previous && $previous instanceof Throwable) {
            $data['previous'] = normalizeException($previous, $depth + 1, true);
        }

        return \array_merge(['thrown_at' => \microtime(true)], $data);
    }
}
/**
 * ESta función sólo loguea a la consola o a un archivo.
 */
if (!\function_exists('dump')) {
    function dump(...$vars): void
    {
        Kint::$enabled_mode = Kint::MODE_CLI;
        $return = Kint::$return;
        Kint::$return = true;
        $fp = \fopen('php://stderr', 'ab');
        \fwrite($fp, d(...$vars));
        \fclose($fp);
        $return = Kint::$return;
        Kint::$return = $return;
    }

    Kint::$aliases[] = 'dump';
}

/**
 * ESta función sólo loguea a la consola o a un archivo.
 */
if (!\function_exists('dd')) {
    function dd(...$vars): void
    {
        dump(...$vars);

        exit();
    }

    Kint::$aliases[] = 'dd';
}

Kint::$aliases[] = 'dump';
Kint::$aliases[] = 'dd';
Kint::$aliases[] = 'd';
