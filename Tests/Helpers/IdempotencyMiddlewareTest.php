<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Helpers;

use Gando\Partner\Helpers\IdempotencyMiddleware;
use PHPUnit\Framework\TestCase;

final class IdempotencyMiddlewareTest extends TestCase
{
    public function test_resolve_deposits_create_key_returns_provided_key(): void
    {
        self::assertSame(
            '550e8400-e29b-41d4-a716-446655440000',
            IdempotencyMiddleware::resolveDepositsCreateKey('550e8400-e29b-41d4-a716-446655440000'),
        );
    }

    public function test_resolve_deposits_create_key_generates_uuid_v4_when_omitted(): void
    {
        $key = IdempotencyMiddleware::resolveDepositsCreateKey(null);

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $key,
        );
    }

    public function test_generate_v4_produces_distinct_values(): void
    {
        self::assertNotSame(
            IdempotencyMiddleware::generateV4(),
            IdempotencyMiddleware::generateV4(),
        );
    }
}
