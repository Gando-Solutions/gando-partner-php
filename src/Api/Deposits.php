<?php

declare(strict_types=1);

namespace Gando\Partner\Api;

use Gando\Partner\Deposits as GeneratedDeposits;
use Gando\Partner\Helpers\IdempotencyMiddleware;
use Gando\Partner\Models\Operations;
use Gando\Partner\Models\Operations\PartnerCaptureBody;
use Gando\Partner\Models\Operations\PartnerCreateDepositBody;
use Gando\Partner\Models\Operations\PartnerDepositEmailsBody;
use Gando\Partner\Models\Operations\PartnerPatchDepositBody;
use Gando\Partner\Models\Operations\PartnerSendDepositMailBody;
use Gando\Partner\Utils\Options;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Partner API deposits resource with safe defaults for {@see create()}.
 */
final readonly class Deposits
{
    public function __construct(
        private GeneratedDeposits $deposits,
        private ?CacheInterface $cache = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Create a deposit for a linked rental operator.
     *
     * When $idempotencyKey is omitted, a UUID v4 is generated automatically and sent as
     * Idempotency-Key. The same key is reused if the SDK retries the HTTP call, so a transient
     * failure does not create two deposits (API deduplication requires this header).
     *
     * @param  ?string  $idempotencyKey  Optional UUID v4; auto-generated when null
     */
    public function create(
        PartnerCreateDepositBody $body,
        ?string $idempotencyKey = null,
        ?Options $options = null,
    ): Operations\DepositsCreateResponse {
        $resolvedKey = $this->resolveCreateIdempotencyKey($body, $idempotencyKey);

        return $this->deposits->create(
            $body,
            $resolvedKey,
            $options,
        );
    }

    private function resolveCreateIdempotencyKey(PartnerCreateDepositBody $body, ?string $idempotencyKey): string
    {
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            return $idempotencyKey;
        }

        if ($this->cache === null) {
            return IdempotencyMiddleware::resolveDepositsCreateKey(null);
        }

        $cacheKey = 'gando_partner:idempotency:create:'.hash('sha256', serialize($body));
        $cached = $this->cache->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            $this->logger?->debug('gando.idempotency.cache_hit', ['cache_key' => $cacheKey]);

            return $cached;
        }

        $generated = IdempotencyMiddleware::resolveDepositsCreateKey(null);
        $this->cache->set($cacheKey, $generated, 24 * 60 * 60);
        $this->logger?->debug('gando.idempotency.cache_set', ['cache_key' => $cacheKey]);

        return $generated;
    }

    public function cancel(
        string $id,
        ?string $idempotencyKey = null,
        ?Options $options = null,
    ): Operations\DepositsCancelResponse {
        return $this->deposits->cancel($id, $idempotencyKey, $options);
    }

    public function capture(
        PartnerCaptureBody $body,
        string $id,
        ?string $idempotencyKey = null,
        ?Options $options = null,
    ): Operations\DepositsCaptureResponse {
        return $this->deposits->capture($body, $id, $idempotencyKey, $options);
    }

    public function delete(string $id, ?Options $options = null): Operations\DepositsDeleteResponse
    {
        return $this->deposits->delete($id, $options);
    }

    public function getCapture(string $id, ?Options $options = null): Operations\DepositsGetCaptureResponse
    {
        return $this->deposits->getCapture($id, $options);
    }

    public function getPaymentMethod(string $id, ?Options $options = null): Operations\DepositsGetPaymentMethodResponse
    {
        return $this->deposits->getPaymentMethod($id, $options);
    }

    public function getPdf(string $id, ?Options $options = null): Operations\DepositsGetPdfResponse
    {
        return $this->deposits->getPdf($id, $options);
    }

    public function list(
        ?Operations\DepositsListRequest $request = null,
        ?Options $options = null,
    ): \Generator {
        return $this->deposits->list($request, $options);
    }

    public function retrieve(string $id, ?Options $options = null): Operations\DepositsRetrieveResponse
    {
        return $this->deposits->retrieve($id, $options);
    }

    public function sendDepositMail(
        PartnerSendDepositMailBody $body,
        string $id,
        ?Options $options = null,
    ): Operations\DepositsSendDepositMailResponse {
        return $this->deposits->sendDepositMail($body, $id, $options);
    }

    public function sendEmails(
        PartnerDepositEmailsBody $body,
        string $id,
        ?Options $options = null,
    ): Operations\DepositsSendEmailsResponse {
        return $this->deposits->sendEmails($body, $id, $options);
    }

    public function update(
        PartnerPatchDepositBody $body,
        string $id,
        ?Options $options = null,
    ): Operations\DepositsUpdateResponse {
        return $this->deposits->update($body, $id, $options);
    }
}
