<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Utils;

use Gando\Partner\Utils\Retry\RetryConfigBackoff;
use Gando\Partner\Utils\Retry\RetryUtils;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class RetryUtilsTest extends TestCase
{
    public function test_retry_wrapper_retries429_then500_then_succeeds_on200(): void
    {
        $attempts = 0;

        $response = RetryUtils::retryWrapper(
            function () use (&$attempts): Response {
                $attempts++;

                return match ($attempts) {
                    1 => new Response(429),
                    2 => new Response(500),
                    default => new Response(200),
                };
            },
            new RetryConfigBackoff(
                initialIntervalMs: 0,
                maxIntervalMs: 0,
                exponent: 1.5,
                maxElapsedTimeMs: 30_000,
                retryConnectionErrors: true,
            ),
            ['429', '5xx'],
        );

        self::assertSame(3, $attempts);
        self::assertSame(200, $response->getStatusCode());
    }

    public function test_retry_wrapper_uses_partner_default_status_codes(): void
    {
        $attempts = 0;

        $response = RetryUtils::retryWrapper(
            function () use (&$attempts): Response {
                $attempts++;

                return $attempts === 1
                    ? new Response(429)
                    : new Response(200);
            },
            new RetryConfigBackoff(
                initialIntervalMs: 0,
                maxIntervalMs: 0,
                exponent: 1.5,
                maxElapsedTimeMs: 30_000,
                retryConnectionErrors: true,
            ),
            ['429', '5xx'],
        );

        self::assertSame(2, $attempts);
        self::assertSame(200, $response->getStatusCode());
    }
}
