<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Api;

use Gando\Partner\Api\Deposits;
use Gando\Partner\Gando;
use Gando\Partner\Models\Components\Security;
use Gando\Partner\Models\Operations\DepositsListRequest;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class DepositsPaginationE2ETest extends TestCase
{
    public function testListIteratesAllPagesWithoutDuplicates(): void
    {
        $capturedQueryParams = [];
        $mock = new MockHandler([
            $this->jsonPageResponse(page: 1, limit: 10, total: 30, numPages: 3, startIndex: 1),
            $this->jsonPageResponse(page: 2, limit: 10, total: 30, numPages: 3, startIndex: 11),
            $this->jsonPageResponse(page: 3, limit: 10, total: 30, numPages: 3, startIndex: 21),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::mapRequest(function (Request $request) use (&$capturedQueryParams): Request {
            parse_str($request->getUri()->getQuery(), $queryParams);
            $capturedQueryParams[] = $queryParams;

            return $request;
        }));

        $sdk = $this->sdkWithHandler($stack);
        $deposits = new Deposits($sdk->deposits);

        $request = new DepositsListRequest(limit: 10);

        $collectedIds = [];
        foreach ($deposits->list($request) as $pageResponse) {
            self::assertSame(200, $pageResponse->statusCode);
            self::assertNotNull($pageResponse->object);

            foreach ($pageResponse->object->data->items as $deposit) {
                $collectedIds[] = $deposit->id;
            }
        }

        self::assertCount(30, $collectedIds);
        self::assertCount(30, array_unique($collectedIds));
        self::assertSame($this->expectedIds(), $collectedIds);

        self::assertCount(3, $capturedQueryParams);
        self::assertSame('1', (string) ($capturedQueryParams[0]['page'] ?? ''));
        self::assertSame('2', (string) ($capturedQueryParams[1]['page'] ?? ''));
        self::assertSame('3', (string) ($capturedQueryParams[2]['page'] ?? ''));
        self::assertSame('10', (string) ($capturedQueryParams[0]['limit'] ?? ''));
        self::assertSame('10', (string) ($capturedQueryParams[1]['limit'] ?? ''));
        self::assertSame('10', (string) ($capturedQueryParams[2]['limit'] ?? ''));
    }

    private function sdkWithHandler(HandlerStack $stack): Gando
    {
        return Gando::builder()
            ->setClient(new GuzzleClient(['handler' => $stack, 'http_errors' => false]))
            ->setServerURL('http://localhost:3000')
            ->setSecurity(new Security(partnerApiKeyAuth: 'gando_pk_test'))
            ->build();
    }

    private function jsonPageResponse(int $page, int $limit, int $total, int $numPages, int $startIndex): Response
    {
        $items = [];
        for ($i = 0; $i < $limit; $i++) {
            $n = $startIndex + $i;
            $items[] = [
                'id' => sprintf('dep_%03d', $n),
                'reference' => sprintf('GAN-%03d', $n),
                'amount' => 800.0,
                'currency' => 'EUR',
                'status' => 'pending',
                'createdAt' => '2026-05-01T10:00:00.000Z',
                'updatedAt' => '2026-05-01T10:00:00.000Z',
                'rentalContract' => sprintf('CTR-%03d', $n),
                'contractStartAt' => null,
                'contractEndAt' => null,
                'clientId' => null,
            ];
        }

        $body = json_encode([
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => $total,
                'numPages' => $numPages,
                'page' => $page,
                'limit' => $limit,
            ],
        ], JSON_THROW_ON_ERROR);

        return new Response(200, ['Content-Type' => ['application/json']], $body);
    }

    /**
     * @return list<string>
     */
    private function expectedIds(): array
    {
        $ids = [];
        for ($i = 1; $i <= 30; $i++) {
            $ids[] = sprintf('dep_%03d', $i);
        }

        return $ids;
    }
}
