# Webhooks

## Overview

## Partner webhooks (v1)

Register **HTTPS endpoints** that receive JSON events for your entire partner integration (same `gando_pk_` authentication as `/api/partner/v1/*`). Use this instead of polling **`GET /api/partner/v1/accounts`** when you need the Gando **`accountId`** as soon as a rental operator completes partner connect.

> **Integration path:** [1. Connect](../../../recipes/01-connect-flow.md) → [2. Webhooks](../../../recipes/02-webhook-lifecycle.md) *(this SDK)* → [3. Deposits](../../../recipes/03-create-deposit.md)

### Authentication

Send your partner API key with **`x-api-key: gando_pk_…`** or **`Authorization: Bearer gando_pk_…`** (same as other v1 routes).

### Events

- **`rental_operator.linked`** — Fired when a rental operator account is linked to your partner via connect. Payload includes Gando `accountId`, your `externalId` from the connect URL, and `partnerId`.
- **`deposit.status_changed`** — Wildcard. Delivered for every deposit status transition (same JSON shape as `#/components/schemas/WebhookDepositStatusChangedEvent`), including optional `data.partnerContext` when the deposit belongs to a linked rental operator.
- **`deposit.activated`** — Delivered only when a deposit transitions to `active`.
- **`deposit.captured`** — Delivered only when a deposit transitions to `captured`.
- **`deposit.expired`** — Delivered only when a deposit transitions to `close` (natural end of contract).
- **`deposit.cancelled`** — Delivered only when a deposit transitions to `cancelled` (manual cancellation).

When a subscription includes both the wildcard and a specific event, **the most specific subscribed event wins** for that transition (single delivery per endpoint). The `event` field of the payload reflects the chosen event so consumers can branch on it directly.

### Signing and headers

**`X-Gando-Signature`** (`sha256=<hex>`), **`X-Gando-Timestamp`** (unix seconds), **`X-Gando-Event`** (event name). Verify HMAC-SHA256 over `<timestamp>.<rawBody>` with your **webhook signing secret** (returned once when you create the endpoint). See the **Webhooks** tag for full verification examples (Node.js, Python, PHP, Go).

### Retries

Failed deliveries are retried on a backoff schedule by Gando's webhook retry job.

**Integration guide:** [Recipe 02 — Receive webhooks](../../../recipes/02-webhook-lifecycle.md) (setup, plain PHP + Symfony receivers, idempotency, local testing).

### Available Operations

* [list](#list) - List partner webhook endpoints
* [create](#create) - Create partner webhook endpoint
* [delete](#delete) - Delete partner webhook endpoint
* [update](#update) - Update partner webhook endpoint
* [rotateSecret](#rotatesecret) - Rotate partner webhook secret
* [getSecret](#getsecret) - Get partner webhook secret
* [test](#test) - Send test partner webhook delivery
* [getDeliveries](#getdeliveries) - List partner webhook deliveries

## list

Retrieve configured webhook endpoints for the authenticated partner. Each item aggregates subscribed event types. Results are paginated with **`page`** and **`limit`** query parameters (same semantics as **`GET /api/partner/v1/deposits`**).

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.list" method="get" path="/api/partner/v1/webhooks" -->
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



$response = $sdk->webhooks->list(
    page: 1,
    limit: 20

);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                | Type                     | Required                 | Description              |
| ------------------------ | ------------------------ | ------------------------ | ------------------------ |
| `page`                   | *?int*                   | :heavy_minus_sign:       | Page number (1-based)    |
| `limit`                  | *?int*                   | :heavy_minus_sign:       | Items per page (max 100) |

### Response

**[?Operations\WebhooksListResponse](../../Models/Operations/WebhooksListResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## create

Create a webhook URL and signing secret for this partner, and subscribe it to the requested event types. Returns the plain signing secret **exactly once**. Default `events` include all available events when omitted.

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.create" method="post" path="/api/partner/v1/webhooks" -->
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

$request = new Operations\CreatePartnerWebhookSubscriptionBody(
    url: 'https://api.example.com/webhooks/gando',
    events: [
        Operations\WebhooksCreateEventRequest::DepositActivated,
        Operations\WebhooksCreateEventRequest::DepositStatusChanged,
    ],
);

$response = $sdk->webhooks->create(
    request: $request
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                                          | Type                                                                                                               | Required                                                                                                           | Description                                                                                                        |
| ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ |
| `$request`                                                                                                         | [Operations\CreatePartnerWebhookSubscriptionBody](../../Models/Operations/CreatePartnerWebhookSubscriptionBody.md) | :heavy_check_mark:                                                                                                 | The request object to use for the request.                                                                         |

### Response

**[?Operations\WebhooksCreateResponse](../../Models/Operations/WebhooksCreateResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## delete

Delete a webhook endpoint and its event subscriptions for the authenticated partner.

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.delete" method="delete" path="/api/partner/v1/webhooks/{id}" -->
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



$response = $sdk->webhooks->delete(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                   | Type                        | Required                    | Description                 |
| --------------------------- | --------------------------- | --------------------------- | --------------------------- |
| `id`                        | *string*                    | :heavy_check_mark:          | Partner webhook endpoint id |

### Response

**[?Operations\WebhooksDeleteResponse](../../Models/Operations/WebhooksDeleteResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## update

Update webhook URL, subscribed event types, or activation status. `{id}` is the partner webhook endpoint id.

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.update" method="patch" path="/api/partner/v1/webhooks/{id}" -->
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

$body = new Operations\UpdatePartnerWebhookSubscriptionBody(
    events: [
        Operations\WebhooksUpdateEventRequest::RentalOperatorLinked,
        Operations\WebhooksUpdateEventRequest::DepositActivated,
    ],
    isActive: true,
);

$response = $sdk->webhooks->update(
    id: '<id>',
    body: $body

);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                                                                                                          | Type                                                                                                               | Required                                                                                                           | Description                                                                                                        | Example                                                                                                            |
| ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ |
| `id`                                                                                                               | *string*                                                                                                           | :heavy_check_mark:                                                                                                 | Partner webhook endpoint id                                                                                        |                                                                                                                    |
| `body`                                                                                                             | [Operations\UpdatePartnerWebhookSubscriptionBody](../../Models/Operations/UpdatePartnerWebhookSubscriptionBody.md) | :heavy_check_mark:                                                                                                 | N/A                                                                                                                | {<br/>"isActive": true,<br/>"events": [<br/>"rental_operator.linked",<br/>"deposit.activated"<br/>]<br/>}          |

### Response

**[?Operations\WebhooksUpdateResponse](../../Models/Operations/WebhooksUpdateResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## rotateSecret

Generate and return a new signing secret for a partner webhook endpoint.

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.rotateSecret" method="post" path="/api/partner/v1/webhooks/{id}/rotate-secret" -->
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



$response = $sdk->webhooks->rotateSecret(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                   | Type                        | Required                    | Description                 |
| --------------------------- | --------------------------- | --------------------------- | --------------------------- |
| `id`                        | *string*                    | :heavy_check_mark:          | Partner webhook endpoint id |

### Response

**[?Operations\WebhooksRotateSecretResponse](../../Models/Operations/WebhooksRotateSecretResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## getSecret

Decrypt and return the current signing secret for a partner webhook endpoint.

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.getSecret" method="get" path="/api/partner/v1/webhooks/{id}/secret" -->
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



$response = $sdk->webhooks->getSecret(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                   | Type                        | Required                    | Description                 |
| --------------------------- | --------------------------- | --------------------------- | --------------------------- |
| `id`                        | *string*                    | :heavy_check_mark:          | Partner webhook endpoint id |

### Response

**[?Operations\WebhooksGetSecretResponse](../../Models/Operations/WebhooksGetSecretResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## test

Sends a sample **`deposit.activated`** payload to the endpoint URL. The endpoint must be subscribed to either `deposit.activated` or the wildcard `deposit.status_changed`.

Sample payload sent by this test and the production dispatcher:

```json
{
  "event": "deposit.activated",
  "createdAt": "2026-03-02T10:00:00.000Z",
  "data": {
    "id": "dep_test_abc123",
    "reference": "GAN-TEST",
    "rentalContract": "CTR-TEST-2026",
    "status": "active",
    "previousStatus": "pending",
    "amountCents": 150000,
    "contractStartAt": null,
    "contractEndAt": null,
    "client": null,
    "partnerContext": {
      "partnerId": "ptr_xxx",
      "partnerName": "Fleetee",
      "externalId": "fleet_operator_42"
    }
  }
}
```

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.test" method="post" path="/api/partner/v1/webhooks/{id}/test" -->
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



$response = $sdk->webhooks->test(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                   | Type                        | Required                    | Description                 |
| --------------------------- | --------------------------- | --------------------------- | --------------------------- |
| `id`                        | *string*                    | :heavy_check_mark:          | Partner webhook endpoint id |

### Response

**[?Operations\WebhooksTestResponse](../../Models/Operations/WebhooksTestResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500, 502                          | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |

## getDeliveries

Paginated delivery history for a partner webhook endpoint.

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.getDeliveries" method="get" path="/api/partner/v1/webhooks/{id}/deliveries" -->
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



$response = $sdk->webhooks->getDeliveries(
    id: '<id>'
);

if ($response->object !== null) {
    // handle response
}
```

### Parameters

| Parameter                     | Type                          | Required                      | Description                   |
| ----------------------------- | ----------------------------- | ----------------------------- | ----------------------------- |
| `id`                          | *string*                      | :heavy_check_mark:            | Partner webhook endpoint id   |
| `limit`                       | *?int*                        | :heavy_minus_sign:            | Page size (1–100, default 20) |
| `offset`                      | *?int*                        | :heavy_minus_sign:            | Number of deliveries to skip  |

### Response

**[?Operations\WebhooksGetDeliveriesResponse](../../Models/Operations/WebhooksGetDeliveriesResponse.md)**

### Errors

| Error Type                        | Status Code                       | Content Type                      |
| --------------------------------- | --------------------------------- | --------------------------------- |
| Errors\ErrorEnvelope              | 400, 401, 403, 404, 409, 422, 429 | application/json                  |
| Errors\ErrorEnvelope              | 500                               | application/json                  |
| Errors\APIException               | 4XX, 5XX                          | \*/\*                             |