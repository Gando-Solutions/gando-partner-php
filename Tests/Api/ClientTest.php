<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Api;

use Gando\Partner\Accounts;
use Gando\Partner\Api\Client;
use Gando\Partner\Api\Deposits;
use Gando\Partner\Api\Events\HttpRequestFinished;
use Gando\Partner\Api\Events\HttpRequestStarted;
use Gando\Partner\Clients;
use Gando\Partner\Webhooks;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

final class ClientTest extends TestCase
{
    public function test_client_exposes_generated_resource_handles(): void
    {
        $api = new Client(apiKey: 'gando_pk_test_key', baseUrl: 'http://localhost:3000');

        self::assertInstanceOf(Accounts::class, $api->accounts);
        self::assertInstanceOf(Clients::class, $api->clients);
        self::assertInstanceOf(Deposits::class, $api->deposits);
        self::assertInstanceOf(Webhooks::class, $api->webhooks);
    }

    public function test_client_does_not_expose_connect_secret(): void
    {
        $api = new Client(apiKey: 'gando_pk_test_key', baseUrl: 'http://localhost:3000');

        self::assertFalse(property_exists($api, 'connectSecret'));
        self::assertFalse(property_exists($api, 'connect_secret'));
    }

    public function test_client_routes_requests_through_injected_psr18_client(): void
    {
        $psr18 = new RecordingPsr18Client(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'success' => true,
            'data' => [
                'accounts' => [],
                'total' => 0,
                'numPages' => 1,
            ],
        ], JSON_THROW_ON_ERROR)));
        $events = new CollectingEventDispatcher();

        $api = new Client(
            apiKey: 'gando_pk_test_key',
            httpClient: $psr18,
            requestFactory: new HttpFactory(),
            logger: new NullLogger(),
            cache: new InMemoryCache(),
            events: $events,
            baseUrl: 'http://localhost:3000',
        );

        $response = $api->accounts->list(limit: 10);
        self::assertSame(200, $response->statusCode);

        self::assertCount(1, $psr18->requests);
        self::assertSame('GET', $psr18->requests[0]->getMethod());
        self::assertStringContainsString('/api/partner/v1/accounts', (string) $psr18->requests[0]->getUri());
        self::assertContainsOnlyInstancesOf(HttpRequestStarted::class, array_filter($events->events, static fn ($event): bool => $event instanceof HttpRequestStarted));
        self::assertContainsOnlyInstancesOf(HttpRequestFinished::class, array_filter($events->events, static fn ($event): bool => $event instanceof HttpRequestFinished));
    }
}

final class RecordingPsr18Client implements Psr18ClientInterface
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

final class CollectingEventDispatcher implements EventDispatcherInterface
{
    /** @var list<object> */
    public array $events = [];

    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        return $event;
    }
}

final class InMemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function get($key, $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->values[(string) $key] = $value;

        return true;
    }

    public function delete($key): bool
    {
        unset($this->values[(string) $key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has($key): bool
    {
        return array_key_exists((string) $key, $this->values);
    }
}
