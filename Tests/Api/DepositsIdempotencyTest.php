<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Api;

use Gando\Partner\Api\Deposits;
use Gando\Partner\Gando;
use Gando\Partner\Helpers\IdempotencyMiddleware;
use Gando\Partner\Models\Components\Security;
use Gando\Partner\Models\Operations\PartnerCreateDepositBody;
use Gando\Partner\Utils\Retry\RetryConfigBackoff;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class DepositsIdempotencyTest extends TestCase
{
    public function test_create_retries_after503_with_same_idempotency_key(): void
    {
        $capturedKeys = [];

        $successBody = json_encode([
            'success' => true,
            'data' => [
                'id' => 'dep_retry_once',
                'reference' => 'GAN-RETRY',
                'status' => 'pending',
                'depositUrl' => null,
                'amount' => 800,
                'createdAt' => '2026-04-01T10:00:00.000Z',
            ],
        ], JSON_THROW_ON_ERROR);

        $mock = new MockHandler([
            new Response(
                429,
                ['Content-Type' => ['application/json']],
                json_encode([
                    'error' => [
                        'code' => 'rate_limited',
                        'message' => 'retry later',
                        'requestId' => 'req_retry_429',
                    ],
                ], JSON_THROW_ON_ERROR),
            ),
            new Response(201, ['Content-Type' => ['application/json']], $successBody),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::mapRequest(function (Request $request) use (&$capturedKeys): Request {
            $capturedKeys[] = $request->getHeaderLine(IdempotencyMiddleware::IDEMPOTENCY_HEADER);

            return $request;
        }));

        $sdk = $this->sdkWithHandler($stack);

        $deposits = new Deposits($sdk->deposits);

        $response = $deposits->create(new PartnerCreateDepositBody(
            accountId: 'acct_test',
            amount: 800.0,
            rentalContract: 'CTR-2026-042',
            contractStartAt: '2026-04-01T00:00:00.000Z',
            contractEndAt: '2026-04-10T23:59:59.000Z',
        ));

        self::assertSame(201, $response->statusCode);
        self::assertNotNull($response->object);
        self::assertSame('dep_retry_once', $response->object->data->id);
        self::assertCount(2, $capturedKeys);
        self::assertNotSame('', $capturedKeys[0]);
        self::assertSame($capturedKeys[0], $capturedKeys[1]);
    }

    public function test_create_respects_caller_provided_idempotency_key(): void
    {
        $capturedKeys = [];

        $mock = new MockHandler([
            new Response(201, ['Content-Type' => ['application/json']], json_encode([
                'success' => true,
                'data' => [
                    'id' => 'dep_1',
                    'reference' => 'GAN-1',
                    'status' => 'pending',
                    'depositUrl' => null,
                    'amount' => 800,
                    'createdAt' => '2026-04-01T10:00:00.000Z',
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::mapRequest(function (Request $request) use (&$capturedKeys): Request {
            $capturedKeys[] = $request->getHeaderLine(IdempotencyMiddleware::IDEMPOTENCY_HEADER);

            return $request;
        }));

        $sdk = $this->sdkWithHandler($stack);
        $deposits = new Deposits($sdk->deposits);
        $provided = '550e8400-e29b-41d4-a716-446655440000';

        $deposits->create(
            new PartnerCreateDepositBody(
                accountId: 'acct_test',
                amount: 800.0,
                rentalContract: 'CTR-2026-042',
                contractStartAt: '2026-04-01T00:00:00.000Z',
                contractEndAt: '2026-04-10T23:59:59.000Z',
            ),
            idempotencyKey: $provided,
        );

        self::assertSame([$provided], $capturedKeys);
    }

    private function sdkWithHandler(HandlerStack $stack): Gando
    {
        return Gando::builder()
            ->setClient(new GuzzleClient(['handler' => $stack, 'http_errors' => false]))
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
    }
}
