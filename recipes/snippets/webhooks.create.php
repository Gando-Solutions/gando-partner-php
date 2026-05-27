<?php

declare(strict_types=1);

/**
 * Create a webhook endpoint (Partner API).
 *
 * Note: the webhook signing secret (gando_whsec_...) is only returned once.
 *
 * Env vars:
 * - GANDO_API_KEY (gando_pk_...)
 * - GANDO_BASE_URL (optional, defaults to SDK default)
 * - GANDO_WEBHOOK_URL (your https endpoint)
 */

require __DIR__.'/../../vendor/autoload.php';

use Gando\Partner\Api\Client;
use Gando\Partner\Models\Operations\CreatePartnerWebhookSubscriptionBody;

$apiKey = getenv('GANDO_API_KEY');
$url = getenv('GANDO_WEBHOOK_URL');

if ($apiKey === false || $apiKey === '' || $url === false || $url === '') {
    fwrite(STDERR, "Missing env vars: GANDO_API_KEY and/or GANDO_WEBHOOK_URL\n");
    exit(1);
}

$api = new Client(
    apiKey: $apiKey,
    baseUrl: getenv('GANDO_BASE_URL') ?: null,
);

$body = new CreatePartnerWebhookSubscriptionBody(url: $url);

$response = $api->webhooks->create($body);

var_dump($response->object);

