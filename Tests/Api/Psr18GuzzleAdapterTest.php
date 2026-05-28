<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Api;

use Gando\Partner\Api\Http\Psr18GuzzleAdapter;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Psr18GuzzleAdapterTest extends TestCase
{
    public function test_send_maps_query_headers_and_body_into_psr18_request(): void
    {
        $psr18 = new RecordingAdapterClient(new Response(200, ['Content-Type' => 'application/json'], '{}'));
        $adapter = new Psr18GuzzleAdapter($psr18, new HttpFactory());

        $adapter->send(new Request('POST', 'https://api.example.test/resource'), [
            'query' => ['page' => 2, 'limit' => 10],
            'headers' => ['X-Test' => 'yes'],
            'body' => '{"hello":"world"}',
        ]);

        self::assertCount(1, $psr18->requests);
        self::assertSame('yes', $psr18->requests[0]->getHeaderLine('X-Test'));
        self::assertSame('page=2&limit=10', $psr18->requests[0]->getUri()->getQuery());
        self::assertSame('{"hello":"world"}', (string) $psr18->requests[0]->getBody());
    }
}

final class RecordingAdapterClient implements Psr18ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    public function __construct(
        private readonly ResponseInterface $response,
    ) {
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return $this->response;
    }
}
