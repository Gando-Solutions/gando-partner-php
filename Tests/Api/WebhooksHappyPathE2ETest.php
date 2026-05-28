<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Api;

use Gando\Partner\Gando;
use Gando\Partner\Models\Components\Security;
use Gando\Partner\Models\Operations\CreatePartnerWebhookSubscriptionBody;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class WebhooksHappyPathE2ETest extends TestCase
{
    public function testWebhooksCreateHappyPathIncludesExpectedHeaders(): void
    {
        $captured = [];
        $webhooks = $this->webhooksWithStack(
            $this->captureStack(
                [new Response(201, ['Content-Type' => ['application/json']], $this->createBody())],
                $captured,
            ),
        );

        $response = $webhooks->create(new CreatePartnerWebhookSubscriptionBody('https://partner.example.test/webhooks'));

        self::assertSame(201, $response->statusCode);
        self::assertNotNull($response->object);
        self::assertSame('wh_123', $response->object->data->id);
        self::assertSame('x-api-key', $captured[0]['auth_header_name']);
        self::assertSame('gando_pk_test', $captured[0]['auth_header_value']);
        self::assertSame('application/json', $captured[0]['content_type']);
    }

    public function testWebhooksListHappyPathIncludesAuthorizationHeader(): void
    {
        $captured = [];
        $webhooks = $this->webhooksWithStack(
            $this->captureStack(
                [new Response(200, ['Content-Type' => ['application/json']], $this->listBody())],
                $captured,
            ),
        );

        foreach ($webhooks->list(page: 1, limit: 10) as $response) {
            self::assertSame(200, $response->statusCode);
            self::assertNotNull($response->object);
            self::assertCount(1, $response->object->data->items);
            break;
        }

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
            $headers = $options['headers'] ?? [];
            $requestHeaders = $request->getHeaders();
            $requestContentType = $request->getHeaderLine('Content-Type');
            $captured[] = [
                'auth_header_name' => array_key_exists('x-api-key', $requestHeaders) ? 'x-api-key' : '',
                'auth_header_value' => $request->getHeaderLine('x-api-key'),
                'content_type' => $requestContentType !== '' ? $requestContentType : $this->headerValue($headers, 'content-type'),
            ];
        }));

        return $stack;
    }

    private function webhooksWithStack(HandlerStack $stack): \Gando\Partner\Webhooks
    {
        $sdk = Gando::builder()
            ->setClient(new GuzzleClient(['handler' => $stack, 'http_errors' => false]))
            ->setServerURL('http://localhost:3000')
            ->setSecurity(new Security(partnerApiKeyAuth: 'gando_pk_test'))
            ->build();

        return $sdk->webhooks;
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

    private function createBody(): string
    {
        return (string) json_encode([
            'success' => true,
            'data' => [
                'id' => 'wh_123',
                'url' => 'https://partner.example.test/webhooks',
                'events' => ['caution.status_changed'],
                'createdAt' => '2026-05-01T10:00:00.000Z',
                'secret' => 'gando_whsec_123',
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function listBody(): string
    {
        return (string) json_encode([
            'success' => true,
            'data' => [
                'items' => [[
                    'id' => 'wh_123',
                    'url' => 'https://partner.example.test/webhooks',
                    'events' => ['caution.status_changed'],
                    'isActive' => true,
                    'createdAt' => '2026-05-01T10:00:00.000Z',
                    'updatedAt' => '2026-05-01T10:00:00.000Z',
                ]],
                'total' => 1,
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
