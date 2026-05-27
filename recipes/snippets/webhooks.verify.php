<?php

declare(strict_types=1);

/**
 * Verify an inbound Gando partner webhook (HMAC-SHA256).
 *
 * @see Gando\Partner\WebhookVerifier
 * @see recipes/snippets/webhooks.create.php — obtain GANDO_WEBHOOK_SECRET once at endpoint creation
 */

require __DIR__.'/../../vendor/autoload.php';

use Gando\Partner\Exceptions\WebhookSignatureException;
use Gando\Partner\WebhookVerifier;

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_GANDO_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_GANDO_TIMESTAMP'] ?? '';
$secret = getenv('GANDO_WEBHOOK_SECRET');

if ($secret === false || $secret === '') {
    http_response_code(500);
    exit('Missing GANDO_WEBHOOK_SECRET');
}

try {
    WebhookVerifier::verify($rawBody, $signature, $timestamp, $secret);
} catch (WebhookSignatureException $e) {
    http_response_code(400);
    exit;
}

$event = $_SERVER['HTTP_X_GANDO_EVENT'] ?? '';
$payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);

// Process $event / $payload asynchronously when possible; respond 2xx quickly.
