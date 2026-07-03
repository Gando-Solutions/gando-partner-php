<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Api;

use Gando\Partner\Gando;
use Gando\Partner\Models\Components\Security;
use Gando\Partner\Utils\Retry\RetryConfigBackoff;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class AccountsRetryTest extends TestCase
{
    public function test_accounts_list_retries429_then503_then_succeeds(): void
    {
        $body = json_encode([
            'success' => true,
            'data' => [
                'accounts' => [],
                'total' => 0,
            ],
        ], JSON_THROW_ON_ERROR);

        $mock = new MockHandler([
            new Response(
                429,
                ['Content-Type' => ['application/json']],
                json_encode([
                    'error' => [
                        'code' => 'rate_limited',
                        'message' => 'Too many requests',
                        'requestId' => 'req_retry_429',
                    ],
                ], JSON_THROW_ON_ERROR),
            ),
            new Response(200, ['Content-Type' => ['application/json']], $body),
        ]);

        $sdk = Gando::builder()
            ->setClient(new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]))
            ->setServerURL('http://localhost:3000')
            ->setSecurity(new Security(partnerApiKeyAuth: 'gando_pk_test'))
            ->setRetryConfig(new RetryConfigBackoff(
                initialIntervalMs: 0,
                maxIntervalMs: 0,
                exponent: 1.5,
                maxElapsedTimeMs: 30_000,
                retryConnectionErrors: true,
            ))
            ->build();

        $response = $sdk->accounts->list(limit: 10);

        self::assertNotNull($response);
        self::assertSame(200, $response->statusCode);
        self::assertNotNull($response->object);
        self::assertCount(0, $mock);
    }
}
