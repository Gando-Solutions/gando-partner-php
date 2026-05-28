<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Api;

use Gando\Partner\Api\Deposits;
use Gando\Partner\Gando;
use Gando\Partner\Models\Components\Security;
use Gando\Partner\Models\Operations\PartnerCaptureBody;
use Gando\Partner\Models\Operations\PartnerCreateDepositBody;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class DepositsHappyPathE2ETest extends TestCase
{
    public function testDepositsCreateHappyPathIncludesExpectedHeaders(): void
    {
        $captured = [];
        $deposits = $this->depositsWithStack(
            $this->captureStack(
                [new Response(201, ['Content-Type' => ['application/json']], $this->createBody())],
                $captured,
            ),
        );

        $response = $deposits->create($this->createDepositBody());

        self::assertSame(201, $response->statusCode);
        self::assertNotNull($response->object);
        self::assertSame('dep_create_1', $response->object->data->id);
        self::assertSame('x-api-key', $captured[0]['auth_header_name']);
        self::assertSame('gando_pk_test', $captured[0]['auth_header_value']);
        self::assertNotSame('', $captured[0]['request_body']);
        self::assertNotSame('', $captured[0]['idempotency_key']);
    }

    public function testDepositsRetrieveHappyPathIncludesAuthorizationHeader(): void
    {
        $captured = [];
        $deposits = $this->depositsWithStack(
            $this->captureStack(
                [new Response(200, ['Content-Type' => ['application/json']], $this->retrieveBody())],
                $captured,
            ),
        );

        $response = $deposits->retrieve('dep_create_1');

        self::assertSame(200, $response->statusCode);
        self::assertNotNull($response->object);
        self::assertSame('dep_create_1', $response->object->data->id);
        self::assertSame('x-api-key', $captured[0]['auth_header_name']);
        self::assertSame('gando_pk_test', $captured[0]['auth_header_value']);
    }

    public function testDepositsCaptureHappyPathIncludesExpectedHeaders(): void
    {
        $captured = [];
        $deposits = $this->depositsWithStack(
            $this->captureStack(
                [new Response(200, ['Content-Type' => ['application/json']], $this->captureBody())],
                $captured,
            ),
        );

        $response = $deposits->capture(
            new PartnerCaptureBody(amount: 10_000, reason: 'damage'),
            'dep_create_1',
            idempotencyKey: 'capture-key-001',
        );

        self::assertSame(200, $response->statusCode);
        self::assertNotNull($response->object);
        self::assertSame(10_000, $response->object->data->capturedAmount);
        self::assertSame('x-api-key', $captured[0]['auth_header_name']);
        self::assertSame('gando_pk_test', $captured[0]['auth_header_value']);
        self::assertNotSame('', $captured[0]['request_body']);
        self::assertSame('capture-key-001', $captured[0]['idempotency_key']);
    }

    public function testDepositsCancelHappyPathIncludesExpectedHeaders(): void
    {
        $captured = [];
        $deposits = $this->depositsWithStack(
            $this->captureStack(
                [new Response(200, ['Content-Type' => ['application/json']], $this->cancelBody())],
                $captured,
            ),
        );

        $response = $deposits->cancel('dep_create_1', idempotencyKey: 'cancel-key-001');

        self::assertSame(200, $response->statusCode);
        self::assertNotNull($response->object);
        self::assertTrue($response->object->data->success);
        self::assertSame('x-api-key', $captured[0]['auth_header_name']);
        self::assertSame('gando_pk_test', $captured[0]['auth_header_value']);
        self::assertSame('cancel-key-001', $captured[0]['idempotency_key']);
    }

    /**
     * @param  array<Response>  $responses
     * @param  array<int, array<string, string>>  $captured
     */
    private function captureStack(array $responses, array &$captured): HandlerStack
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::tap(function (RequestInterface $request, array $options) use (&$captured): void {
            $headers = $options['headers'] ?? [];
            $requestHeaders = $request->getHeaders();
            $requestContentType = $request->getHeaderLine('Content-Type');
            $captured[] = [
                'auth_header_name' => array_key_exists('x-api-key', $requestHeaders) ? 'x-api-key' : '',
                'auth_header_value' => $request->getHeaderLine('x-api-key'),
                'content_type' => $requestContentType !== '' ? $requestContentType : $this->headerValue($headers, 'content-type'),
                'idempotency_key' => $request->getHeaderLine('Idempotency-Key') !== ''
                    ? $request->getHeaderLine('Idempotency-Key')
                    : (string) ($headers['Idempotency-Key'] ?? ''),
                'request_body' => (string) $request->getBody(),
            ];
        }));

        return $stack;
    }

    private function depositsWithStack(HandlerStack $stack): Deposits
    {
        $sdk = Gando::builder()
            ->setClient(new GuzzleClient(['handler' => $stack, 'http_errors' => false]))
            ->setServerURL('http://localhost:3000')
            ->setSecurity(new Security(partnerApiKeyAuth: 'gando_pk_test'))
            ->build();

        return new Deposits($sdk->deposits);
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    private function headerValue(array $headers, string $target): string
    {
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) !== $target) {
                continue;
            }

            if (is_array($value)) {
                return (string) ($value[0] ?? '');
            }

            return (string) $value;
        }

        return '';
    }

    private function createDepositBody(): PartnerCreateDepositBody
    {
        return new PartnerCreateDepositBody(
            accountId: 'acct_test',
            amount: 800.0,
            rentalContract: 'CTR-2026-042',
            contractStartAt: '2026-04-01T00:00:00.000Z',
            contractEndAt: '2026-04-10T23:59:59.000Z',
        );
    }

    private function createBody(): string
    {
        return (string) json_encode([
            'success' => true,
            'data' => [
                'id' => 'dep_create_1',
                'reference' => 'GAN-CREATE-1',
                'status' => 'pending',
                'deposit_url' => null,
                'amount' => 800.0,
                'created_at' => '2026-04-01T10:00:00.000Z',
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function retrieveBody(): string
    {
        return (string) json_encode([
            'success' => true,
            'data' => [
                'id' => 'dep_create_1',
                'reference' => 'GAN-CREATE-1',
                'amount' => 800.0,
                'currency' => 'EUR',
                'status' => 'pending',
                'createdAt' => '2026-04-01T10:00:00.000Z',
                'updatedAt' => '2026-04-01T10:00:00.000Z',
                'rentalContract' => 'CTR-2026-042',
                'contractStartAt' => null,
                'contractEndAt' => null,
                'clientId' => null,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function captureBody(): string
    {
        return (string) json_encode([
            'success' => true,
            'data' => [
                'capturedAmount' => 10_000,
                'status' => 'succeeded',
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function cancelBody(): string
    {
        return (string) json_encode([
            'success' => true,
            'data' => [
                'success' => true,
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
