<?php

declare(strict_types=1);

/**
 * Create a deposit for a linked rental operator (Partner API).
 *
 * Env vars:
 * - GANDO_API_KEY (gando_pk_...)
 * - GANDO_BASE_URL (optional, defaults to SDK default)
 * - GANDO_ACCOUNT_ID (linked rental operator account id)
 */

require __DIR__.'/../../vendor/autoload.php';

use Gando\Partner\Api\Client;
use Gando\Partner\Models\Operations\PartnerCreateDepositBody;

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

$body = new PartnerCreateDepositBody(
    accountId: $accountId,
    amount: 800.0,
    rentalContract: 'CTR-2026-042',
    contractStartAt: '2026-04-01T00:00:00.000Z',
    contractEndAt: '2026-04-10T23:59:59.000Z',
    clientId: null,
    inlineRedirect: true,
    returnUrl: 'https://partner.example/checkout/complete',
);

$response = $api->deposits->create($body);

var_dump($response->object);

