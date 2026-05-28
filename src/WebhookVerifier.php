<?php

declare(strict_types=1);

namespace Gando\Partner;

use Gando\Partner\Exceptions\WebhookSignatureException;

class WebhookVerifier
{
    /**
     * Verifies a Gando webhook signature.
     *
     * @param  string  $rawBody  Raw request body (do NOT use parsed JSON).
     * @param  string  $signatureHeader  Value of X-Gando-Signature header (format: "sha256=<hex>").
     * @param  string  $timestampHeader  Value of X-Gando-Timestamp header (Unix seconds).
     * @param  string  $secret  Webhook signing secret (gando_whsec_...).
     * @param  int  $toleranceSeconds  Max age of webhook in seconds (default 300).
     *
     * @throws WebhookSignatureException on invalid signature, expired timestamp, or malformed inputs.
     */
    public static function verify(
        string $rawBody,
        string $signatureHeader,
        string $timestampHeader,
        string $secret,
        int $toleranceSeconds = 300,
    ): void {
        $timestamp = filter_var($timestampHeader, FILTER_VALIDATE_INT);
        if ($timestamp === false || $timestamp <= 0) {
            throw new WebhookSignatureException('invalid');
        }

        $ageSeconds = abs(time() - $timestamp);
        if ($ageSeconds > $toleranceSeconds) {
            throw new WebhookSignatureException('expired');
        }

        if ($signatureHeader === '' || !str_starts_with($signatureHeader, 'sha256=')) {
            throw new WebhookSignatureException('invalid');
        }

        $signedPayload = $timestampHeader.'.'.$rawBody;
        $expected = 'sha256='.hash_hmac('sha256', $signedPayload, $secret);

        if (strlen($signatureHeader) !== strlen($expected)) {
            throw new WebhookSignatureException('invalid');
        }

        if (! hash_equals($expected, $signatureHeader)) {
            throw new WebhookSignatureException('invalid');
        }
    }
}
