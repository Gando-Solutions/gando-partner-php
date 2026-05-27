<?php

declare(strict_types=1);

namespace Gando\Partner\Api;

use Gando\Partner\Deposits as GeneratedDeposits;
use Gando\Partner\Helpers\IdempotencyMiddleware;
use Gando\Partner\Models\Operations;
use Gando\Partner\Models\Operations\DepositsListQueryParamStatus;
use Gando\Partner\Models\Operations\DepositsListRequest;
use Gando\Partner\Models\Operations\IncludeCounts;
use Gando\Partner\Models\Operations\PartnerCaptureBody;
use Gando\Partner\Models\Operations\PartnerCreateDepositBody;
use Gando\Partner\Models\Operations\PartnerDepositEmailsBody;
use Gando\Partner\Models\Operations\PartnerPatchDepositBody;
use Gando\Partner\Models\Operations\PartnerSendDepositMailBody;
use Gando\Partner\Models\Operations\SortBy;
use Gando\Partner\Models\Operations\SortOrder;
use Gando\Partner\Utils\Options;

/**
 * Partner API deposits resource with safe defaults for {@see create()}.
 */
final class Deposits
{
    public function __construct(
        private readonly GeneratedDeposits $deposits,
    ) {
    }

    /**
     * Create a deposit for a linked rental operator.
     *
     * When $idempotencyKey is omitted, a UUID v4 is generated automatically and sent as
     * Idempotency-Key. The same key is reused if the SDK retries the HTTP call, so a transient
     * failure does not create two deposits (API deduplication requires this header).
     *
     * @param  PartnerCreateDepositBody  $body
     * @param  ?string  $idempotencyKey  Optional UUID v4; auto-generated when null
     */
    public function create(
        PartnerCreateDepositBody $body,
        ?string $idempotencyKey = null,
        ?Options $options = null,
    ): Operations\DepositsCreateResponse {
        return $this->deposits->create(
            $body,
            IdempotencyMiddleware::resolveDepositsCreateKey($idempotencyKey),
            $options,
        );
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

    /**
     * Iterate all deposits across pages (API uses <code>page</code> + <code>limit</code>).
     *
     * @param  array<string, mixed>|Operations\DepositsListRequest|null  $request  Array keys may be camelCase or snake_case for common filters
     */
    public function list(
        array|Operations\DepositsListRequest|null $request = null,
        ?Options $options = null,
    ): DepositsListIterable {
        return new DepositsListIterable(
            $this->deposits,
            self::normalizeListRequest($request),
            $options,
        );
    }

    /**
     * Single list request (one HTTP call); same as the generated {@see GeneratedDeposits::list()}.
     */
    public function listPage(
        ?Operations\DepositsListRequest $request = null,
        ?Options $options = null,
    ): Operations\DepositsListResponse {
        return $this->deposits->list($request, $options);
    }

    /**
     * @param  array<string, mixed>|Operations\DepositsListRequest|null  $request
     */
    private static function normalizeListRequest(array|Operations\DepositsListRequest|null $request): DepositsListRequest
    {
        if ($request instanceof DepositsListRequest) {
            return $request;
        }
        if ($request === null) {
            return new DepositsListRequest();
        }

        $accountId = $request['accountId'] ?? $request['account_id'] ?? null;
        $clientId = $request['clientId'] ?? $request['client_id'] ?? null;
        $page = isset($request['page']) ? (int) $request['page'] : 1;
        $limit = isset($request['limit']) ? (int) $request['limit'] : 20;
        $limit = min(100, max(1, $limit));
        $page = max(1, $page);

        $statusRaw = $request['status'] ?? null;
        $status = null;
        if (is_array($statusRaw)) {
            $mapped = [];
            foreach ($statusRaw as $s) {
                if ($s instanceof DepositsListQueryParamStatus) {
                    $mapped[] = $s;
                    continue;
                }
                if (is_string($s)) {
                    $e = DepositsListQueryParamStatus::tryFrom($s);
                    if ($e !== null) {
                        $mapped[] = $e;
                    }
                }
            }
            $status = $mapped !== [] ? $mapped : null;
        }

        return new DepositsListRequest(
            accountId: $accountId !== null ? (string) $accountId : null,
            sortBy: $request['sortBy'] ?? null,
            sortOrder: $request['sortOrder'] ?? null,
            status: $status,
            clientId: $clientId !== null ? (string) $clientId : null,
            startAtFrom: isset($request['startAtFrom']) ? (string) $request['startAtFrom'] : (isset($request['start_at_from']) ? (string) $request['start_at_from'] : null),
            startAtTo: isset($request['startAtTo']) ? (string) $request['startAtTo'] : (isset($request['start_at_to']) ? (string) $request['start_at_to'] : null),
            expiresAtFrom: isset($request['expiresAtFrom']) ? (string) $request['expiresAtFrom'] : (isset($request['expires_at_from']) ? (string) $request['expires_at_from'] : null),
            expiresAtTo: isset($request['expiresAtTo']) ? (string) $request['expiresAtTo'] : (isset($request['expires_at_to']) ? (string) $request['expires_at_to'] : null),
            includeCounts: $request['includeCounts'] ?? $request['include_counts'] ?? null,
            amountMin: isset($request['amountMin']) ? (float) $request['amountMin'] : (isset($request['amount_min']) ? (float) $request['amount_min'] : null),
            amountMax: isset($request['amountMax']) ? (float) $request['amountMax'] : (isset($request['amount_max']) ? (float) $request['amount_max'] : null),
            page: $page,
            limit: $limit,
        );
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
