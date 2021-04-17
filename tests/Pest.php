<?php

/**
 * CTOhm - Transbank Custom Clients
 */

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;

uses(Tests\WebpayPlusTestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
 */

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
expect()->extend('toHaveTestRecords', function (?int $expectedRecords = null): void {
    expect($this->value)->toBeInstanceOf(LoggerInterface::class);
    expect($this->value->getHandlers())->toBeArray();
    $testHandler = \array_filter($this->value->getHandlers(), fn (HandlerInterface $handler) => $handler instanceof TestHandler);

    if (\count($testHandler) > 0) {
        $logRecords = \array_values($testHandler)[0]->getRecords();
        expect($logRecords)->toBeArray();

        if (null !== $expectedRecords) {
            expect($logRecords)->toHaveCount(2);
        }
    }
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
 */

function something()
{
    // ..
}
