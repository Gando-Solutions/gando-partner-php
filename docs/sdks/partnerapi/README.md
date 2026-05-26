# PartnerAPI

## Overview

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

`deposit_url` is only returned when `inline_redirect` is `true`. After the tenant finishes on Gando, they are sent to `return_url` with query params **`caution_id`** and **`caution_status`** (`secured` | `declined` | `abandoned`). Always confirm the final state with **webhooks** (subscribe under **`/api/partner/webhooks`** — see **Partner webhooks** tag) or **GET /api/partner/deposits/{id}**.

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

* [accountsList](#accountslist) - List linked rental operator accounts
* [accountsRevoke](#accountsrevoke) - Revoke partner ↔ rental operator link
* [clientsList](#clientslist) - List clients across linked rental operator accounts
* [clientsCreate](#clientscreate) - Create a client for a linked rental operator account
* [clientsUpdate](#clientsupdate) - Update a partner-accessible client
* [depositsList](#depositslist) - List deposits
* [depositsCreate](#depositscreate) - Create a deposit for a linked rental operator
* [depositsRetrieve](#depositsretrieve) - Get deposit by id
* [depositsDelete](#depositsdelete) - Delete or archive a deposit
* [depositsUpdate](#depositsupdate) - Update deposit (change client or cancel pending payment)
* [depositsGetCapture](#depositsgetcapture) - Get latest capture for a deposit
* [depositsCapture](#depositscapture) - Create a capture (encaissement)
* [depositsSendEmails](#depositssendemails) - Send deposit link to multiple emails
* [depositsSendDepositMail](#depositssenddepositmail) - Send deposit link to one email
* [depositsCancel](#depositscancel) - Close deposit (status close + optional email)
* [depositsGetPaymentMethod](#depositsgetpaymentmethod) - Masked card info for the deposit
* [depositsGetPdf](#depositsgetpdf) - Download deposit summary PDF

## accountsList

Returns rental operator accounts linked to your partner. Filter with `status`: `active` (default), `revoked`, or `all`.

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



$response = $sdk->partnerAPI->accountsList(

);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                           | Type                                                                                                | Required                                                                                            | Description                                                                                         |
| --------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `status`                                                                                            | [?Operations\AccountsListQueryParamStatus](../../Models/Operations/AccountsListQueryParamStatus.md) | :heavy_minus_sign:                                                                                  | Filter linked accounts: `active` (default, operable links), `revoked` (disconnected), or `all`.     |

### Response

**[?Operations\AccountsListResponse](../../Models/Operations/AccountsListResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## accountsRevoke

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



$response = $sdk->partnerAPI->accountsRevoke(
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

## clientsList

Returns paginated clients for all active linked rental operator accounts, or for a specific linked account when `accountId` is provided.

### Example Usage

<!-- UsageSnippet language="php" operationID="clients.list" method="get" path="/api/partner/clients" -->
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



$response = $sdk->partnerAPI->clientsList(
    page: 1,
    limit: 20

);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                             | Type                                                  | Required                                              | Description                                           |
| ----------------------------------------------------- | ----------------------------------------------------- | ----------------------------------------------------- | ----------------------------------------------------- |
| `accountId`                                           | *?string*                                             | :heavy_minus_sign:                                    | Filter clients to this linked rental operator account |
| `page`                                                | *?int*                                                | :heavy_minus_sign:                                    | Page number (1-based)                                 |
| `limit`                                               | *?int*                                                | :heavy_minus_sign:                                    | Items per page (max 100)                              |
| `search`                                              | *?string*                                             | :heavy_minus_sign:                                    | Case-insensitive search on name, email, or company    |

### Response

**[?Operations\ClientsListResponse](../../Models/Operations/ClientsListResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## clientsCreate

Creates a client and returns its id. This id can then be sent as optional `client_id` in `POST /api/partner/deposits`. This endpoint is idempotent by email within account: when a client already exists, it returns 200 with the existing id.

### Example Usage

<!-- UsageSnippet language="php" operationID="clients.create" method="post" path="/api/partner/clients" -->
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



$response = $sdk->partnerAPI->clientsCreate(
    body: new Components\ParticulierClient(
        accountId: 'acc_7k2m9x',
        firstName: 'Jean',
        lastName: 'Dupont',
        email: 'jean.dupont@example.com',
        clientType: Components\ParticulierClientClientType::Particulier,
    )
);

if ($response->twoHundredApplicationJsonObject !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                                                                                             | Type                                                                                                                                                                  | Required                                                                                                                                                              | Description                                                                                                                                                           | Example                                                                                                                                                               |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `body`                                                                                                                                                                | [Components\ParticulierClient\|Components\ProfessionnelClient](../../Models/Components/PartnerCreateClientBody.md)                                                    | :heavy_check_mark:                                                                                                                                                    | N/A                                                                                                                                                                   | {<br/>"accountId": "acc_7k2m9x",<br/>"firstName": "Jean",<br/>"lastName": "Dupont",<br/>"email": "jean.dupont@example.com",<br/>"clientType": "particulier"<br/>}     |
| `idempotencyKey`                                                                                                                                                      | *?string*                                                                                                                                                             | :heavy_minus_sign:                                                                                                                                                    | Optional UUID v4 for request deduplication (24h). Same key + same body replays the cached response; same key + different body returns 409 `idempotency_key_conflict`. |                                                                                                                                                                       |

### Response

**[?Operations\ClientsCreateResponse](../../Models/Operations/ClientsCreateResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500, 503                          | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## clientsUpdate

Updates a client that belongs to one of the partner's linked rental operator accounts.

### Example Usage

<!-- UsageSnippet language="php" operationID="clients.update" method="patch" path="/api/partner/clients/{id}" -->
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



$response = $sdk->partnerAPI->clientsUpdate(
    id: '<id>',
    body: new Components\ParticulierPartnerClientPatch(
        phone: '+33698765432',
    )

);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                                                                | Type                                                                                                                                     | Required                                                                                                                                 | Description                                                                                                                              | Example                                                                                                                                  |
| ---------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| `id`                                                                                                                                     | *string*                                                                                                                                 | :heavy_check_mark:                                                                                                                       | Client unique identifier                                                                                                                 |                                                                                                                                          |
| `body`                                                                                                                                   | [Components\ParticulierPartnerClientPatch\|Components\ProfessionnelPartnerClientPatch](../../Models/Components/PartnerClientPatchBody.md) | :heavy_check_mark:                                                                                                                       | N/A                                                                                                                                      | {<br/>"phone": "+33698765432"<br/>}                                                                                                      |

### Response

**[?Operations\ClientsUpdateResponse](../../Models/Operations/ClientsUpdateResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsList

Lists deposits across **all active** rental operator accounts linked to your partner.

Pass **`account_id`** to return only deposits for that single linked rental operator account.

Repeat query parameter **`status`** to filter by several statuses (e.g. `?status=pending&status=active`).

When `include_counts=true` **and** `account_id` is set, the response includes per-status counts for that account.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.list" method="get" path="/api/partner/deposits" -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;
use Gando\Partner\Models\Operations;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();

$request = new Operations\DepositsListRequest();

$response = $sdk->partnerAPI->depositsList(
    request: $request
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                        | Type                                                                             | Required                                                                         | Description                                                                      |
| -------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| `$request`                                                                       | [Operations\DepositsListRequest](../../Models/Operations/DepositsListRequest.md) | :heavy_check_mark:                                                               | The request object to use for the request.                                       |

### Response

**[?Operations\DepositsListResponse](../../Models/Operations/DepositsListResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsCreate

Creates a deposit on behalf of a linked rental operator (`account_id`). Optionally set `client_id` to attach an existing client from the same rental operator account.

**Inline redirect:** set `inline_redirect: true` and `return_url` to receive `deposit_url` and send the tenant straight to Gando. Same redirect query parameters as the rental operator API: `caution_id`, `caution_status`.

**Idempotency-Key:** optional UUID v4 header; replays return the same **201** and `data.id` within 24h when the body is unchanged.

See the **Partner API** tag for curl/fetch examples.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.create" method="post" path="/api/partner/deposits" -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;
use Gando\Partner\Models\Operations;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();

$body = new Operations\PartnerCreateDepositBody(
    accountId: 'acc_7k2m9x',
    amount: 800,
    rentalContract: 'CTR-2026-0421',
    contractStartAt: '2026-06-01T10:00:00.000Z',
    contractEndAt: '2026-06-15T18:00:00.000Z',
    clientId: 'cli_9f3k2a',
    inlineRedirect: true,
    returnUrl: 'https://partner.example.com/deposits/return',
);

$response = $sdk->partnerAPI->depositsCreate(
    body: $body
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                                                                                                                                                                                                                          | Type                                                                                                                                                                                                                                                                                               | Required                                                                                                                                                                                                                                                                                           | Description                                                                                                                                                                                                                                                                                        | Example                                                                                                                                                                                                                                                                                            |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `body`                                                                                                                                                                                                                                                                                             | [Operations\PartnerCreateDepositBody](../../Models/Operations/PartnerCreateDepositBody.md)                                                                                                                                                                                                         | :heavy_check_mark:                                                                                                                                                                                                                                                                                 | N/A                                                                                                                                                                                                                                                                                                | {<br/>"account_id": "acc_7k2m9x",<br/>"amount": 800,<br/>"rental_contract": "CTR-2026-0421",<br/>"contract_start_at": "2026-06-01T10:00:00.000Z",<br/>"contract_end_at": "2026-06-15T18:00:00.000Z",<br/>"client_id": "cli_9f3k2a",<br/>"inline_redirect": true,<br/>"return_url": "https://partner.example.com/deposits/return"<br/>} |
| `idempotencyKey`                                                                                                                                                                                                                                                                                   | *?string*                                                                                                                                                                                                                                                                                          | :heavy_minus_sign:                                                                                                                                                                                                                                                                                 | Optional UUID v4 for request deduplication (24h). Same key + same body replays the cached response; same key + different body returns 409 `idempotency_key_conflict`.                                                                                                                              |                                                                                                                                                                                                                                                                                                    |

### Response

**[?Operations\DepositsCreateResponse](../../Models/Operations/DepositsCreateResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500, 503                          | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsRetrieve

Returns the same deposit shape as the rental operator API (`CautionItem` fields). Deposit must belong to a linked rental operator.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.retrieve" method="get" path="/api/partner/deposits/{id}" -->
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



$response = $sdk->partnerAPI->depositsRetrieve(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                           | Type                                | Required                            | Description                         |
| ----------------------------------- | ----------------------------------- | ----------------------------------- | ----------------------------------- |
| `id`                                | *string*                            | :heavy_check_mark:                  | Deposit (caution) unique identifier |

### Response

**[?Operations\DepositsRetrieveResponse](../../Models/Operations/DepositsRetrieveResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsDelete

Same semantics as rental-operator delete: remove when allowed, otherwise archive. Response uses top-level **`message`** (`Deleted` or `Archived`), not `data`.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.delete" method="delete" path="/api/partner/deposits/{id}" -->
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



$response = $sdk->partnerAPI->depositsDelete(
    id: '<id>'
);

if ($response->partnerDeleteDepositResponse !== null) {
    // handle response
}
```

### Parameters

| Parameter                           | Type                                | Required                            | Description                         |
| ----------------------------------- | ----------------------------------- | ----------------------------------- | ----------------------------------- |
| `id`                                | *string*                            | :heavy_check_mark:                  | Deposit (caution) unique identifier |

### Response

**[?Operations\DepositsDeleteResponse](../../Models/Operations/DepositsDeleteResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsUpdate

Exactly one of:
- `client_id` — reassign client (must belong to the deposit's rental operator account)
- `action: "cancel"` — void the in-flight deposit payment and set status to `cancelled`

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.update" method="patch" path="/api/partner/deposits/{id}" -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;
use Gando\Partner\Models\Operations;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();

$body = new Operations\PartnerPatchDepositBody(
    clientId: 'cli_9f3k2a',
);

$response = $sdk->partnerAPI->depositsUpdate(
    id: '<id>',
    body: $body

);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                | Type                                                                                     | Required                                                                                 | Description                                                                              | Example                                                                                  |
| ---------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `id`                                                                                     | *string*                                                                                 | :heavy_check_mark:                                                                       | Deposit (caution) unique identifier                                                      |                                                                                          |
| `body`                                                                                   | [Operations\PartnerPatchDepositBody](../../Models/Operations/PartnerPatchDepositBody.md) | :heavy_check_mark:                                                                       | N/A                                                                                      | {<br/>"client_id": "cli_9f3k2a"<br/>}                                                    |

### Response

**[?Operations\DepositsUpdateResponse](../../Models/Operations/DepositsUpdateResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500, 502                          | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsGetCapture

Prefers the latest **paid** capture; if none, returns the most recent capture of any status. **404** when no capture exists yet.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.getCapture" method="get" path="/api/partner/deposits/{id}/capture" -->
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



$response = $sdk->partnerAPI->depositsGetCapture(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                           | Type                                | Required                            | Description                         |
| ----------------------------------- | ----------------------------------- | ----------------------------------- | ----------------------------------- |
| `id`                                | *string*                            | :heavy_check_mark:                  | Deposit (caution) unique identifier |

### Response

**[?Operations\DepositsGetCaptureResponse](../../Models/Operations/DepositsGetCaptureResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsCapture

Charge the tenant card for the given amount in **cents** (min 1000). Requires a payment method on the deposit and capture-ready payout configuration on the linked rental operator's Gando account.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.capture" method="post" path="/api/partner/deposits/{id}/capture" -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;
use Gando\Partner\Models\Operations;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();

$body = new Operations\PartnerCaptureBody(
    amount: 50000,
    reason: 'Vehicle damage — bumper scratch',
);

$response = $sdk->partnerAPI->depositsCapture(
    id: '<id>',
    body: $body

);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                                                                                             | Type                                                                                                                                                                  | Required                                                                                                                                                              | Description                                                                                                                                                           | Example                                                                                                                                                               |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`                                                                                                                                                                  | *string*                                                                                                                                                              | :heavy_check_mark:                                                                                                                                                    | Deposit (caution) unique identifier                                                                                                                                   |                                                                                                                                                                       |
| `body`                                                                                                                                                                | [Operations\PartnerCaptureBody](../../Models/Operations/PartnerCaptureBody.md)                                                                                        | :heavy_check_mark:                                                                                                                                                    | N/A                                                                                                                                                                   | {<br/>"amount": 50000,<br/>"reason": "Vehicle damage — bumper scratch"<br/>}                                                                                          |
| `idempotencyKey`                                                                                                                                                      | *?string*                                                                                                                                                             | :heavy_minus_sign:                                                                                                                                                    | Optional UUID v4 for request deduplication (24h). Same key + same body replays the cached response; same key + different body returns 409 `idempotency_key_conflict`. |                                                                                                                                                                       |

### Response

**[?Operations\DepositsCaptureResponse](../../Models/Operations/DepositsCaptureResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500, 503                          | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsSendEmails

Sends the deposit completion link to each address. Per-recipient success is returned in `results`; failed sends do not fail the whole request.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.sendEmails" method="post" path="/api/partner/deposits/{id}/email" -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;
use Gando\Partner\Models\Operations;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();

$body = new Operations\PartnerDepositEmailsBody(
    emails: [
        'tenant@example.com',
        'tenant.spouse@example.com',
    ],
);

$response = $sdk->partnerAPI->depositsSendEmails(
    id: '<id>',
    body: $body

);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                  | Type                                                                                       | Required                                                                                   | Description                                                                                | Example                                                                                    |
| ------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| `id`                                                                                       | *string*                                                                                   | :heavy_check_mark:                                                                         | Deposit (caution) unique identifier                                                        |                                                                                            |
| `body`                                                                                     | [Operations\PartnerDepositEmailsBody](../../Models/Operations/PartnerDepositEmailsBody.md) | :heavy_check_mark:                                                                         | N/A                                                                                        | {<br/>"emails": [<br/>"tenant@example.com",<br/>"tenant.spouse@example.com"<br/>]<br/>}    |

### Response

**[?Operations\DepositsSendEmailsResponse](../../Models/Operations/DepositsSendEmailsResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsSendDepositMail

Single-recipient variant of `/email`. Returns provider `messageId` when available.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.sendDepositMail" method="post" path="/api/partner/deposits/{id}/send-deposit-mail" -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;
use Gando\Partner\Models\Operations;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();

$body = new Operations\PartnerSendDepositMailBody(
    email: 'tenant@example.com',
);

$response = $sdk->partnerAPI->depositsSendDepositMail(
    id: '<id>',
    body: $body

);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                      | Type                                                                                           | Required                                                                                       | Description                                                                                    | Example                                                                                        |
| ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| `id`                                                                                           | *string*                                                                                       | :heavy_check_mark:                                                                             | Deposit (caution) unique identifier                                                            |                                                                                                |
| `body`                                                                                         | [Operations\PartnerSendDepositMailBody](../../Models/Operations/PartnerSendDepositMailBody.md) | :heavy_check_mark:                                                                             | N/A                                                                                            | {<br/>"email": "tenant@example.com"<br/>}                                                      |

### Response

**[?Operations\DepositsSendDepositMailResponse](../../Models/Operations/DepositsSendDepositMailResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsCancel

Sets the deposit status to `close` (end-of-contract closure) and may send the closed-caution email. **Different from** `PATCH …` with `action: cancel` which voids the in-flight deposit payment and sets status to `cancelled`.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.cancel" method="post" path="/api/partner/deposits/{id}/cancel" -->
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



$response = $sdk->partnerAPI->depositsCancel(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                                                                                             | Type                                                                                                                                                                  | Required                                                                                                                                                              | Description                                                                                                                                                           |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`                                                                                                                                                                  | *string*                                                                                                                                                              | :heavy_check_mark:                                                                                                                                                    | Deposit (caution) unique identifier                                                                                                                                   |
| `idempotencyKey`                                                                                                                                                      | *?string*                                                                                                                                                             | :heavy_minus_sign:                                                                                                                                                    | Optional UUID v4 for request deduplication (24h). Same key + same body replays the cached response; same key + different body returns 409 `idempotency_key_conflict`. |

### Response

**[?Operations\DepositsCancelResponse](../../Models/Operations/DepositsCancelResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500, 503                          | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsGetPaymentMethod

Requires payment processing to be configured and a saved payment method on the deposit.

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.getPaymentMethod" method="get" path="/api/partner/deposits/{id}/payment-method" -->
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



$response = $sdk->partnerAPI->depositsGetPaymentMethod(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                           | Type                                | Required                            | Description                         |
| ----------------------------------- | ----------------------------------- | ----------------------------------- | ----------------------------------- |
| `id`                                | *string*                            | :heavy_check_mark:                  | Deposit (caution) unique identifier |

### Response

**[?Operations\DepositsGetPaymentMethodResponse](../../Models/Operations/DepositsGetPaymentMethodResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500, 502                          | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## depositsGetPdf

Returns raw **application/pdf** bytes (not JSON).

### Example Usage

<!-- UsageSnippet language="php" operationID="deposits.getPdf" method="get" path="/api/partner/deposits/{id}/pdf" -->
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



$response = $sdk->partnerAPI->depositsGetPdf(
    id: '<id>'
);

if ($response->bytes !== null) {
    // handle response
}
```

### Parameters

| Parameter                           | Type                                | Required                            | Description                         |
| ----------------------------------- | ----------------------------------- | ----------------------------------- | ----------------------------------- |
| `id`                                | *string*                            | :heavy_check_mark:                  | Deposit (caution) unique identifier |

### Response

**[?Operations\DepositsGetPdfResponse](../../Models/Operations/DepositsGetPdfResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |