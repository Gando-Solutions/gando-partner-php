<?php

declare(strict_types=1);

/**
 * Create a client (Partner API).
 *
 * Env vars:
 * - GANDO_API_KEY (gando_pk_...)
 * - GANDO_BASE_URL (optional, defaults to SDK default)
 * - GANDO_ACCOUNT_ID (linked rental operator account id)
 */

require __DIR__.'/../../vendor/autoload.php';

use Gando\Partner\Api\Client;
use Gando\Partner\Models\Components\ParticulierClient;
use Gando\Partner\Models\Components\ParticulierClientClientType;

$apiKey = getenv('GANDO_API_KEY');
$accountId = getenv('GANDO_ACCOUNT_ID');

if ($apiKey === false || $apiKey === '' || $accountId === false || $accountId === '') {
    fwrite(STDERR, "Missing env vars: GANDO_API_KEY and/or GANDO_ACCOUNT_ID\n");
    exit(1);
}

$api = new Client(
    apiKey: $apiKey,
    baseUrl: getenv('GANDO_BASE_URL') ?: null,
);

$body = new ParticulierClient(
    firstName: 'Jean',
    lastName: 'Dupont',
    email: 'tenant@example.com',
    clientType: ParticulierClientClientType::Particulier,
    accountId: $accountId,
);

$response = $api->clients->create($body);

$clientId = $response->twoHundredAndOneApplicationJsonObject?->data->id
    ?? $response->twoHundredApplicationJsonObject?->data->id;

var_dump($clientId);

