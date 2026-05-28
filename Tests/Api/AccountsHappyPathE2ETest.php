<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Api;

use Gando\Partner\Gando;
use Gando\Partner\Models\Components\Security;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class AccountsHappyPathE2ETest extends TestCase
{
    /**
     * accounts.create is not present in this SDK; this covers the closest account mutation.
     */
    public function testAccountsRevokeHappyPathIncludesAuthorizationHeader(): void
    {
        $captured = [];
        $accounts = $this->accountsWithStack(
            $this->captureStack(
                [new Response(200, ['Content-Type' => ['application/json']], $this->revokeBody())],
                $captured,
            ),
        );

        $response = $accounts->revoke('acct_123');

        self::assertSame(200, $response->statusCode);
        self::assertNotNull($response->object);
        self::assertSame('revoked', $response->object->data->status->value);
        self::assertSame('x-api-key', $captured[0]['auth_header_name']);
        self::assertSame('gando_pk_test', $captured[0]['auth_header_value']);
    }

    /**
     * @param  array<Response>  $responses
     * @param  array<int, array<string, string>>  $captured
     */
    private function captureStack(array $responses, array &$captured): HandlerStack
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::tap(function (RequestInterface $request, array $options) use (&$captured): void {
            $requestHeaders = $request->getHeaders();
            $captured[] = [
                'auth_header_name' => array_key_exists('x-api-key', $requestHeaders) ? 'x-api-key' : '',
                'auth_header_value' => $request->getHeaderLine('x-api-key'),
            ];
        }));

        return $stack;
    }

    private function accountsWithStack(HandlerStack $stack): \Gando\Partner\Accounts
    {
        $sdk = Gando::builder()
            ->setClient(new GuzzleClient(['handler' => $stack, 'http_errors' => false]))
            ->setServerURL('http://localhost:3000')
            ->setSecurity(new Security(partnerApiKeyAuth: 'gando_pk_test'))
            ->build();

        return $sdk->accounts;
    }

    private function revokeBody(): string
    {
        return (string) json_encode([
            'success' => true,
            'data' => [
                'status' => 'revoked',
                'revoked_at' => '2026-05-01T10:00:00.000Z',
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
