<?php

declare(strict_types=1);

namespace Gando\Partner\Api;

use Gando\Partner\Accounts;
use Gando\Partner\Clients;
use Gando\Partner\Gando;
use Gando\Partner\Api\Http\Psr18GuzzleAdapter;
use Gando\Partner\Models\Components\Security;
use Gando\Partner\Webhooks;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class Client
{
    public Accounts $accounts;
    public Clients $clients;
    public Deposits $deposits;
    public Webhooks $webhooks;

    public function __construct(
        public string $apiKey,
        ?Psr18ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null,
        ?EventDispatcherInterface $events = null,
        ?string $baseUrl = null,
    ) {
        $resolvedHttpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $resolvedRequestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $transport = new Psr18GuzzleAdapter($resolvedHttpClient, $resolvedRequestFactory, $logger, $events);

        $sdk = Gando::builder()
            ->setClient($transport)
            ->setSecurity(new Security(partnerApiKeyAuth: $apiKey))
            ->setServerUrl($baseUrl ?? Gando::SERVERS[0])
            ->build();

        $this->accounts = $sdk->accounts;
        $this->clients = $sdk->clients;
        $this->deposits = new Deposits($sdk->deposits, $cache, $logger);
        $this->webhooks = $sdk->webhooks;
    }
}
