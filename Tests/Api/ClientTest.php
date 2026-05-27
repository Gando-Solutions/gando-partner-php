<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Api;

use Gando\Partner\Accounts;
use Gando\Partner\Api\Client;
use Gando\Partner\Clients;
use Gando\Partner\Api\Deposits;
use Gando\Partner\Webhooks;
use PHPUnit\Framework\TestCase;

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
}

