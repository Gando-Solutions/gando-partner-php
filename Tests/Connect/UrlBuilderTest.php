<?php

declare(strict_types=1);

namespace Gando\Partner\Tests\Connect;

use Gando\Partner\Connect\UrlBuilder;
use PHPUnit\Framework\TestCase;

final class UrlBuilderTest extends TestCase
{
    private const SECRET = 'gando_cs_unit_test_secret';
    private const PARTNER = 'fleetee';
    private const BASE_URL = 'https://dashboard.gando.app';

    public function test_signup_url_uses_expected_hmac_vector(): void
    {
        $builder = new UrlBuilder(
            connectSecret: self::SECRET,
            partnerSlug: self::PARTNER,
            baseUrl: self::BASE_URL,
        );

        $url = $builder->signupUrl(externalId: 'ext-42', timestamp: 1700000000);

        $parts = parse_url($url);
        self::assertIsArray($parts);
        self::assertSame('https', $parts['scheme'] ?? null);
        self::assertSame('dashboard.gando.app', $parts['host'] ?? null);
        self::assertSame('/register', $parts['path'] ?? null);

        $query = [];
        parse_str($parts['query'] ?? '', $query);

        self::assertSame('fleetee', $query['partner'] ?? null);
        self::assertSame('ext-42', $query['external_id'] ?? null);
        self::assertSame('1700000000', $query['ts'] ?? null);

        $expectedPayload = 'fleetee.ext-42.1700000000';
        $expectedSig = hash_hmac('sha256', $expectedPayload, self::SECRET);
        self::assertSame($expectedSig, $query['sig'] ?? null);
    }

    public function test_signup_url_changes_signature_when_external_id_changes(): void
    {
        $builder = new UrlBuilder(self::SECRET, self::PARTNER, self::BASE_URL);

        $a = $builder->signupUrl(externalId: 'ext-42', timestamp: 1700000000);
        $b = $builder->signupUrl(externalId: 'ext-43', timestamp: 1700000000);

        self::assertNotSame($a, $b);
    }

    public function test_signup_url_changes_signature_when_timestamp_changes(): void
    {
        $builder = new UrlBuilder(self::SECRET, self::PARTNER, self::BASE_URL);

        $a = $builder->signupUrl(externalId: 'ext-42', timestamp: 1700000000);
        $b = $builder->signupUrl(externalId: 'ext-42', timestamp: 1700000001);

        self::assertNotSame($a, $b);
    }

    public function test_signup_url_includes_optional_params_when_provided(): void
    {
        $builder = new UrlBuilder(self::SECRET, self::PARTNER, self::BASE_URL);

        $url = $builder->signupUrl(
            externalId: 'ext-42',
            email: 'ops@example.com',
            name: 'Fleetee Ops',
            returnUrl: 'https://partner.example/return',
            timestamp: 1700000000,
        );

        $query = [];
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);

        self::assertSame('ops@example.com', $query['email'] ?? null);
        self::assertSame('Fleetee Ops', $query['name'] ?? null);
        self::assertSame('https://partner.example/return', $query['return_url'] ?? null);
    }
}

