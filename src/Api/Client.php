<?php

declare(strict_types=1);

namespace Gando\Partner\Api;

use Gando\Partner\Accounts;
use Gando\Partner\Clients;
use Gando\Partner\Api\Deposits;
use Gando\Partner\Gando;
use Gando\Partner\Models\Components\Security;
use Gando\Partner\Webhooks;

final class Client
{
    public readonly Accounts $accounts;
    public readonly Clients $clients;
    public readonly Deposits $deposits;
    public readonly Webhooks $webhooks;

    public function __construct(
        public readonly string $apiKey,
        ?string $baseUrl = null,
    ) {
        $sdk = Gando::builder()
            ->setSecurity(new Security(partnerApiKeyAuth: $apiKey))
            ->setServerUrl($baseUrl ?? Gando::SERVERS[0])
            ->build();

        $this->accounts = $sdk->accounts;
        $this->clients = $sdk->clients;
        $this->deposits = new Deposits($sdk->deposits);
        $this->webhooks = $sdk->webhooks;
    }
}

