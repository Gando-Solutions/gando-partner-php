# Accounts

## Overview

Manage rental operator accounts linked to your partner integration.

## Partner API vs rental operator API

These routes live under `/api/partner/*` and use **partner API keys** only. They are **not** the same as rental operator (loueur) API keys under `/api/cautions`, `/api/clients`, etc.

### Authentication (partner keys)

- Keys are issued by Gando and always start with the prefix **`gando_pk_`**.
- Send the raw key in either:
  - Header **`x-api-key: gando_pk_…`**, or
  - Header **`Authorization: Bearer gando_pk_…`**
- Missing key or wrong prefix → **401** `error.code`: **`missing_api_key`**.
- Unknown key → **401** `error.code`: **`invalid_api_key`**.
- Revoked key → **401** `error.code`: **`api_key_revoked`**.

### Error responses (ErrorEnvelope)

All documented 4xx/5xx responses use the same JSON shape (`#/components/schemas/ErrorEnvelope`):

```json
{
  "error": {
    "code": "invalid_request",
    "message": "Human-readable error message.",
    "requestId": "req_abc123",
    "details": { "field": "status" }
  }
}
```

### Idempotency-Key (mutating POST)

Optional header on **`POST /api/partner/deposits`**, **`POST /api/partner/clients`**, **`POST /api/partner/deposits/{id}/capture`**, and **`POST /api/partner/deposits/{id}/cancel`**. Send a **UUID v4** per logical operation. Within **24 hours**, repeating the same key with the **same JSON body** returns the **cached HTTP status and response** (no duplicate side effects). Reusing the key with a **different body** returns **409** with `error.code`: `idempotency_key_conflict`. Invalid key format → **400** with `error.code`: `invalid_idempotency_key`. Requires **`REDIS_URL`** on the server; if Redis is unavailable while the header is sent → **503** with `error.code`: `redis_unavailable`.

### curl (partner key)

```bash
curl -sS -X GET "https://gando.app/api/partner/deposits?page=1&limit=20" \
  -H "x-api-key: gando_pk_YOUR_PARTNER_KEY"
```

```bash
curl -sS -X GET "https://gando.app/api/partner/deposits?page=1&limit=20" \
  -H "Authorization: Bearer gando_pk_YOUR_PARTNER_KEY"
```

### Create client then deposit (recommended flow)

```bash
curl -sS -X POST "https://gando.app/api/partner/clients" \
  -H "Content-Type: application/json" \
  -H "x-api-key: gando_pk_YOUR_PARTNER_KEY" \
  -d '{
    "account_id": "acct_rental_operator_uuid",
    "email": "tenant@example.com",
    "first_name": "Jean",
    "last_name": "Dupont"
  }'
```

Then reuse the returned client `id` as optional `client_id` when creating a deposit.

### Create a deposit (full example)

```bash
curl -sS -X POST "https://gando.app/api/partner/deposits" \
  -H "Content-Type: application/json" \
  -H "x-api-key: gando_pk_YOUR_PARTNER_KEY" \
  -d '{
    "account_id": "acct_rental_operator_uuid",
    "amount": 800,
    "rental_contract": "CTR-2026-042",
    "contract_start_at": "2026-04-01T00:00:00.000Z",
    "contract_end_at": "2026-04-10T23:59:59.000Z",
    "client_id": "cli_123",
    "inline_redirect": true,
    "return_url": "https://partner.example/checkout/complete"
  }'
```

### JavaScript (fetch) — create + redirect

```javascript
const BASE = "https://gando.app";
const PARTNER_KEY = process.env.GANDO_PARTNER_KEY; // gando_pk_…

const res = await fetch(`${BASE}/api/partner/deposits`, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "x-api-key": PARTNER_KEY,
  },
  body: JSON.stringify({
    account_id: "acct_rental_operator_uuid",
    amount: 800,
    rental_contract: "CTR-2026-042",
    contract_start_at: "2026-04-01T00:00:00.000Z",
    contract_end_at: "2026-04-10T23:59:59.000Z",
    client_id: "cli_123",
    inline_redirect: true,
    return_url: "https://partner.example/checkout/complete",
  }),
});
const json = await res.json();
if (!json.success) throw new Error(json.error);
const { deposit_url } = json.data;
if (deposit_url) window.location.assign(deposit_url);
```

`deposit_url` is only returned when `inline_redirect` is `true`. After the tenant finishes on Gando, they are sent to `return_url` with query params **`caution_id`** and **`caution_status`** (`secured` | `declined` | `abandoned`). Always confirm the final state with **webhooks** (subscribe under **`/api/partner/webhooks`** — see **Webhooks** tag) or **GET /api/partner/deposits/{id}**.

### Webhooks — `partner_context` on caution events

When a deposit was created through the Partner API, webhook payloads (`caution.status_changed` wildcard or any of the specific `caution.activated` / `caution.captured` / `caution.expired` / `caution.cancelled`) may include **`data.partner_context`**:

- `partner_id` — Gando partner id
- `partner_name` — display name
- `external_id` — your rental-operator / account identifier on the link, or `null`

Schema reference: `#/components/schemas/WebhookCautionStatusChangedEvent` (field `data.partner_context`).

### Verify webhook HMAC (Node.js)

Use the **same** signing rules as rental-operator webhooks (`X-Gando-Signature`, `X-Gando-Timestamp`, payload `timestamp.rawBody`). See the **Webhooks** tag description in this document for full HMAC examples (Node, Python, PHP, Go).

```javascript
import crypto from "node:crypto";

export function verifyGandoWebhook(rawBody, signature, timestamp, secret) {
  if (!signature?.startsWith("sha256=")) return false;
  const signedPayload = `${timestamp}.${rawBody}`;
  const expected =
    "sha256=" +
    crypto.createHmac("sha256", secret).update(signedPayload).digest("hex");
  const a = Buffer.from(signature);
  const b = Buffer.from(expected);
  return a.length === b.length && crypto.timingSafeEqual(a, b);
}
```

### HTTP status codes (Partner API)

| Code | Meaning |
|------|---------|
| **401** | Missing partner key, invalid or revoked `gando_pk_` key |
| **403** | Partner is not allowed to use this rental operator `account_id` / deposit |
| **404** | Unknown deposit id, capture not found, etc. |
| **409** | Idempotency key conflict (`idempotency_key_conflict`) or future linking conflicts |
| **422** | Semantic validation failure (reserved; many cases still return **400** today) |
| **429** | Rate limited (middleware: 200 req/min per IP+path on `/api/*`; `Retry-After` header) |
| **400** | Invalid JSON body, query params, or business rule (e.g. capture amount) |
| **500** / **502** | Server or upstream payment-processor errors |

Rental-operator tokens (`gando_…` without `_pk_`) and session cookies are documented under **Deposits** / **Clients** / **Webhooks** — do **not** use them for `/api/partner/*`.

### Available Operations

* [list](#list) - List linked rental operator accounts
* [revoke](#revoke) - Revoke partner ↔ rental operator link

## list

Returns rental operator accounts linked to your partner. Filter with `status`: `active` (default), `revoked`, or `all`. Results are paginated with **`page`** and **`limit`** query parameters (same semantics as **`GET /api/partner/deposits`**).

### Example Usage

<!-- UsageSnippet language="php" operationID="accounts.list" method="get" path="/api/partner/accounts" -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();



$responses = $sdk->accounts->list(
    page: 1,
    limit: 20

);


foreach ($responses as $response) {
    if ($response->statusCode === 200) {
        // handle response
    }
}
```

### Parameters

| Parameter                                                                                           | Type                                                                                                | Required                                                                                            | Description                                                                                         |
| --------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `status`                                                                                            | [?Operations\AccountsListQueryParamStatus](../../Models/Operations/AccountsListQueryParamStatus.md) | :heavy_minus_sign:                                                                                  | Filter linked accounts: `active` (default, operable links), `revoked` (disconnected), or `all`.     |
| `page`                                                                                              | *?int*                                                                                              | :heavy_minus_sign:                                                                                  | Page number (1-based)                                                                               |
| `limit`                                                                                             | *?int*                                                                                              | :heavy_minus_sign:                                                                                  | Items per page (max 100)                                                                            |

### Response

**[?Operations\AccountsListResponse](../../Models/Operations/AccountsListResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## revoke

Revokes the link between your partner and the given rental operator `account_id`. Further deposit operations for that account return **403** until re-linked.

### Example Usage

<!-- UsageSnippet language="php" operationID="accounts.revoke" method="delete" path="/api/partner/accounts/{id}" -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();



$response = $sdk->accounts->revoke(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                | Type                                     | Required                                 | Description                              |
| ---------------------------------------- | ---------------------------------------- | ---------------------------------------- | ---------------------------------------- |
| `id`                                     | *string*                                 | :heavy_check_mark:                       | Rental operator account id (`accountId`) |

### Response

**[?Operations\AccountsRevokeResponse](../../Models/Operations/AccountsRevokeResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |