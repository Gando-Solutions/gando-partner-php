<?php

declare(strict_types=1);

/**
 * List webhook endpoints (Partner API).
 *
 * Env vars:
 * - GANDO_API_KEY (gando_pk_...)
 * - GANDO_BASE_URL (optional, defaults to SDK default)
 */

require __DIR__.'/../../vendor/autoload.php';

use Gando\Partner\Api\Client;

$apiKey = getenv('GANDO_API_KEY');
if ($apiKey === false || $apiKey === '') {
    fwrite(STDERR, "Missing env var: GANDO_API_KEY\n");
    exit(1);
}

$api = new Client(
    apiKey: $apiKey,
    baseUrl: getenv('GANDO_BASE_URL') ?: null,
);

$response = $api->webhooks->list();

var_dump($response->object);

