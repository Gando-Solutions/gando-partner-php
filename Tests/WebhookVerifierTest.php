<?php

declare(strict_types=1);

namespace Gando\Partner\Tests;

use Gando\Partner\Exceptions\WebhookSignatureException;
use Gando\Partner\WebhookVerifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WebhookVerifierTest extends TestCase
{
    private const SECRET = 'gando_whsec_test_secret_123';

    private string $rawBody;

    protected function setUp(): void
    {
        $this->rawBody = json_encode([
            'event' => 'caution.status_changed',
            'created_at' => '2026-03-02T10:00:00.000Z',
            'data' => [
                'id' => 'clxxx123',
                'status' => 'active',
            ],
        ], JSON_THROW_ON_ERROR);
    }

    public function testVerifyAcceptsValidSignature(): void
    {
        $timestamp = (string) time();
        $signature = $this->sign($this->rawBody, $timestamp, self::SECRET);

        WebhookVerifier::verify($this->rawBody, $signature, $timestamp, self::SECRET);

        $this->addToAssertionCount(1);
    }

    public function testVerifyRejectsTamperedBody(): void
    {
        $timestamp = (string) time();
        $signature = $this->sign($this->rawBody, $timestamp, self::SECRET);
        $tamperedBody = json_encode([
            'event' => 'caution.status_changed',
            'data' => ['id' => 'clxxx123', 'status' => 'captured'],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('invalid');

        WebhookVerifier::verify($tamperedBody, $signature, $timestamp, self::SECRET);
    }

    public function testVerifyRejectsWrongSecret(): void
    {
        $timestamp = (string) time();
        $signature = $this->sign($this->rawBody, $timestamp, self::SECRET);

        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('invalid');

        WebhookVerifier::verify($this->rawBody, $signature, $timestamp, 'gando_whsec_wrong');
    }

    public function testVerifyRejectsExpiredTimestamp(): void
    {
        $timestamp = (string) (time() - 301);
        $signature = $this->sign($this->rawBody, $timestamp, self::SECRET);

        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('expired');

        WebhookVerifier::verify($this->rawBody, $signature, $timestamp, self::SECRET);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedSignatureProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'invalid prefix' => ['invalid_prefix'];
        yield 'too short hex' => ['sha256=abc'];
    }

    #[DataProvider('malformedSignatureProvider')]
    public function testVerifyRejectsMalformedSignature(string $signature): void
    {
        $timestamp = (string) time();

        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('invalid');

        WebhookVerifier::verify($this->rawBody, $signature, $timestamp, self::SECRET);
    }

    private function sign(string $rawBody, string $timestamp, string $secret): string
    {
        $signedPayload = $timestamp.'.'.$rawBody;

        return 'sha256='.hash_hmac('sha256', $signedPayload, $secret);
    }
}
