<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Utils;

use Gando\Partner\Utils\Retry\RetryConfigBackoff;
use Gando\Partner\Utils\Retry\RetryUtils;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class RetryStrategyTest extends TestCase
{
    public function test_retry_strategy_retries_twice_and_honors_retry_after_timing(): void
    {
        $attempts = 0;
        $start = microtime(true);

        $response = RetryUtils::retryWrapper(
            function () use (&$attempts): Response {
                $attempts++;

                return match ($attempts) {
                    1 => new Response(429, ['Retry-After' => ['1']]),
                    2 => new Response(500, ['Retry-After' => ['1']]),
                    default => new Response(200),
                };
            },
            new RetryConfigBackoff(
                initialIntervalMs: 0,
                maxIntervalMs: 2000,
                exponent: 1.5,
                maxElapsedTimeMs: 10_000,
                retryConnectionErrors: true,
            ),
            ['429', '5xx'],
        );

        $elapsedMs = (microtime(true) - $start) * 1000;

        self::assertSame(3, $attempts, 'Expected initial attempt + 2 retries.');
        self::assertSame(200, $response->getStatusCode());
        self::assertGreaterThanOrEqual(1_900, $elapsedMs);
        self::assertLessThan(3_500, $elapsedMs);
    }
}
