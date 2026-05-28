<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Helpers;

use Gando\Partner\Hooks\BeforeRequestContext;
use Gando\Partner\Hooks\HookContext;
use Gando\Partner\Helpers\IdempotencyMiddleware;
use Gando\Partner\SDKConfiguration;
use GuzzleHttp\Psr7\Request;
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

    public function test_before_request_adds_key_for_deposits_create_without_header(): void
    {
        $middleware = new IdempotencyMiddleware();
        $request = new Request('POST', 'https://api.example.test/api/partner/deposits');

        $result = $middleware->beforeRequest($this->contextFor('deposits.create'), $request);

        self::assertTrue($result->hasHeader(IdempotencyMiddleware::IDEMPOTENCY_HEADER));
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $result->getHeaderLine(IdempotencyMiddleware::IDEMPOTENCY_HEADER),
        );
    }

    public function test_before_request_keeps_existing_key_for_deposits_create(): void
    {
        $middleware = new IdempotencyMiddleware();
        $request = (new Request('POST', 'https://api.example.test/api/partner/deposits'))
            ->withHeader(IdempotencyMiddleware::IDEMPOTENCY_HEADER, 'existing-idempotency-key');

        $result = $middleware->beforeRequest($this->contextFor('deposits.create'), $request);

        self::assertSame(
            'existing-idempotency-key',
            $result->getHeaderLine(IdempotencyMiddleware::IDEMPOTENCY_HEADER),
        );
    }

    public function test_before_request_ignores_non_deposits_operation(): void
    {
        $middleware = new IdempotencyMiddleware();
        $request = new Request('POST', 'https://api.example.test/api/partner/webhooks');

        $result = $middleware->beforeRequest($this->contextFor('webhooks.create'), $request);

        self::assertFalse($result->hasHeader(IdempotencyMiddleware::IDEMPOTENCY_HEADER));
    }

    private function contextFor(string $operationId): BeforeRequestContext
    {
        $sdkConfiguration = new SDKConfiguration();
        $hookContext = new HookContext(
            config: $sdkConfiguration,
            baseURL: 'https://api.example.test',
            operationID: $operationId,
            oauth2Scopes: null,
            securitySource: null,
        );

        return new BeforeRequestContext($hookContext);
    }
}
