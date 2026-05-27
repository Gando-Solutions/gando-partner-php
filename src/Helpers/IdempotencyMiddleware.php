<?php

declare(strict_types=1);

namespace Gando\Partner\Helpers;

use Gando\Partner\Hooks\BeforeRequestContext;
use Gando\Partner\Hooks\BeforeRequestHook;
use Psr\Http\Message\RequestInterface;

/**
 * Partner API idempotency helpers for {@see \Gando\Partner\Api\Deposits::create()}.
 *
 * The API deduplicates POST /api/partner/deposits only when an Idempotency-Key header is sent.
 * This helper generates a UUID v4 when the caller omits a key so SDK retries replay the same
 * logical operation instead of creating duplicate deposits.
 */
final class IdempotencyMiddleware implements BeforeRequestHook
{
    public const DEPOSITS_CREATE_OPERATION_ID = 'deposits.create';

    public const IDEMPOTENCY_HEADER = 'Idempotency-Key';

    /**
     * Returns the caller key or a new UUID v4 suitable for the Idempotency-Key header.
     */
    public static function resolveDepositsCreateKey(?string $idempotencyKey): string
    {
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            return $idempotencyKey;
        }

        return self::generateV4();
    }

    public static function generateV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Adds Idempotency-Key on deposits.create when the outgoing request has none.
     * Prefer {@see \Gando\Partner\Api\Deposits::create()} which sets the key before the HTTP layer.
     */
    public function beforeRequest(BeforeRequestContext $context, RequestInterface $request): RequestInterface
    {
        if ($context->operationID !== self::DEPOSITS_CREATE_OPERATION_ID) {
            return $request;
        }

        if (strtoupper($request->getMethod()) !== 'POST') {
            return $request;
        }

        if ($request->hasHeader(self::IDEMPOTENCY_HEADER)) {
            return $request;
        }

        return $request->withHeader(self::IDEMPOTENCY_HEADER, self::generateV4());
    }
}
