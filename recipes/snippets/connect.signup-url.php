<?php

declare(strict_types=1);

/**
 * Build a signed Gando Partner Connect signup URL.
 *
 * Env vars:
 * - GANDO_CONNECT_SECRET (gando_cs_...)
 * - GANDO_PARTNER_SLUG (e.g. "fleetee")
 * - GANDO_DASHBOARD_BASE_URL (e.g. "https://dashboard.gando.app")
 */

require __DIR__.'/../../vendor/autoload.php';

use Gando\Partner\Connect\UrlBuilder;

$connectSecret = getenv('GANDO_CONNECT_SECRET');
$partnerSlug = getenv('GANDO_PARTNER_SLUG');
$baseUrl = getenv('GANDO_DASHBOARD_BASE_URL') ?: 'https://dashboard.gando.app';

if ($connectSecret === false || $connectSecret === '' || $partnerSlug === false || $partnerSlug === '') {
    fwrite(STDERR, "Missing env vars: GANDO_CONNECT_SECRET and/or GANDO_PARTNER_SLUG\n");
    exit(1);
}

$builder = new UrlBuilder(
    connectSecret: $connectSecret,
    partnerSlug: $partnerSlug,
    baseUrl: $baseUrl,
);

$signupUrl = $builder->signupUrl(
    externalId: 'fleet_acct_42',
    email: 'ops@example.com',
    name: 'Fleetee Ops',
    returnUrl: 'https://partner.example/connect/return',
);

echo $signupUrl."\n";

