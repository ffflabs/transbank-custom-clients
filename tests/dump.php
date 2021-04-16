<?php

/**
 * CTOhm - Transbank Custom Clients
 */

use Kint\Kint;
use Psr\Http\Message\RequestInterface;

$projectRootPath = $_ENV['APP_BASE_PATH'] ?? (\dirname(__DIR__));
$basePath = $projectRootPath;
\defined('APP_BASE_PATH') || \define('APP_BASE_PATH', $projectRootPath);

\define('IN_CONSOLE', \getenv('APP_RUNNING_IN_CONSOLE') || (\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)));
\define('KINT_FILE_PATH', IN_CONSOLE ? 'php://stderr' : \sprintf('%s/storage/%s', APP_BASE_PATH, 'logs/kint.debug.txt'));

if (!\function_exists('tap')) {
    function tap($subject, Closure $callback)
    {
        $callback($subject);

        return $subject;
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
