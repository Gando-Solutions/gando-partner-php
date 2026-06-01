# Deposits

## Overview

Create, retrieve, update, cancel, and capture deposits on behalf of linked rental operators.

### Available Operations

* [list](#list) - List deposits
* [create](#create) - Create a deposit for a linked rental operator
* [retrieve](#retrieve) - Get deposit by id
* [delete](#delete) - Delete or archive a deposit
* [update](#update) - Update deposit (change client or cancel pending payment)
* [getCapture](#getcapture) - Get latest capture for a deposit
* [capture](#capture) - Create a capture (encaissement)
* [sendEmails](#sendemails) - Send deposit link to multiple emails
* [sendDepositMail](#senddepositmail) - Send deposit link to one email
* [cancel](#cancel) - Close deposit (status close + optional email)
* [getPaymentMethod](#getpaymentmethod) - Masked card info for the deposit
* [getPdf](#getpdf) - Download deposit summary PDF

## list

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

$response = $sdk->deposits->list(
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

## create

Creates a deposit on behalf of a linked rental operator (`account_id`). Optionally set `client_id` to attach an existing client from the same rental operator account.

**Inline redirect:** set `inline_redirect: true` and `return_url` to receive `deposit_url` and send the tenant straight to Gando. Same redirect query parameters as the rental operator API: `caution_id`, `caution_status`.

**Idempotency-Key:** optional UUID v4 header; replays return the same **201** and `data.id` within 24h when the body is unchanged. The PHP SDK (`Gando\Partner\Api\Client`) auto-generates this header when omitted so retries stay idempotent.

See the **Accounts** tag description for authentication details and curl/fetch examples.

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

$response = $sdk->deposits->create(
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

## retrieve

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



$response = $sdk->deposits->retrieve(
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

## delete

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



$response = $sdk->deposits->delete(
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

## update

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

$response = $sdk->deposits->update(
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

## getCapture

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



$response = $sdk->deposits->getCapture(
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

## capture

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

$response = $sdk->deposits->capture(
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

## sendEmails

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

$response = $sdk->deposits->sendEmails(
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

## sendDepositMail

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

$response = $sdk->deposits->sendDepositMail(
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

## cancel

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



$response = $sdk->deposits->cancel(
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

## getPaymentMethod

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



$response = $sdk->deposits->getPaymentMethod(
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

## getPdf

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



$response = $sdk->deposits->getPdf(
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