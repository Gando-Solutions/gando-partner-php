<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Utils;

use Gando\Partner\Utils\Retry\RetryConfigBackoff;
use Gando\Partner\Utils\Retry\RetryUtils;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class RetryStrategyTest extends TestCase
{
    public function testRetryStrategyRetriesTwiceAndHonorsRetryAfterTiming(): void
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
                initialInterval: 0,
                maxInterval: 2000,
                exponent: 1.5,
                maxElapsedTime: 10_000,
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
