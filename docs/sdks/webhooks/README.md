# Webhooks

## Overview

## Partner webhooks

Register **HTTPS endpoints** that receive JSON events for your entire partner integration (same `gando_pk_` authentication as `/api/partner/*`). Use this instead of polling **`GET /api/partner/accounts`** when you need the Gando **`account_id`** as soon as a rental operator completes partner connect (signup or login with signed `partner_connect`).

### Authentication

Send your partner API key with **`x-api-key: gando_pk_…`** or **`Authorization: Bearer gando_pk_…`** (same as other Partner API routes).

### Events

- **`rental_operator.linked`** — Fired when a rental operator account is linked to your partner via connect. Payload includes Gando `account_id`, your `external_id` from the connect URL, and `partner_id`.
- **`caution.status_changed`** — Wildcard. Delivered for every deposit status transition (same JSON shape as rental-operator webhooks: `#/components/schemas/WebhookCautionStatusChangedEvent`), including optional `data.partner_context` when the deposit belongs to a linked rental operator.
- **`caution.activated`** — Specific event. Delivered only when a deposit transitions to `active`.
- **`caution.captured`** — Specific event. Delivered only when a deposit transitions to `captured`.
- **`caution.expired`** — Specific event. Delivered only when a deposit transitions to `close` (natural end of contract).
- **`caution.cancelled`** — Specific event. Delivered only when a deposit transitions to `cancelled` (manual cancellation).

When a subscription includes both the wildcard and a specific event, **the most specific subscribed event wins** for that transition (single delivery per endpoint). The `event` field of the payload reflects the chosen event so consumers can branch on it directly.

### Signing and headers

Identical to rental-operator webhooks: **`X-Gando-Signature`** (`sha256=<hex>`), **`X-Gando-Timestamp`** (unix seconds), **`X-Gando-Event`** (event name). Verify HMAC-SHA256 over `<timestamp>.<rawBody>` with your **webhook signing secret** (returned once when you create the endpoint). See the **Webhooks** tag in this document for full verification examples (Node.js, Python, PHP, Go).

### Retries

Failed deliveries are retried on a backoff schedule by Gando’s webhook retry job (same behaviour as rental-operator webhook deliveries).

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

Retrieve configured webhook endpoints for the authenticated partner (`gando_pk_` key). Each item aggregates subscribed event types. Results are paginated with **`page`** and **`limit`** query parameters (same semantics as **`GET /api/partner/deposits`**).

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.list" method="get" path="/api/partner/webhooks" -->
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

Create a webhook URL and signing secret for this partner, and subscribe it to the requested event types. Returns the plain signing secret **exactly once**. Default `events` include all available events (`rental_operator.linked`, `caution.status_changed`, `caution.activated`, `caution.captured`, `caution.expired`, `caution.cancelled`) when omitted.

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.create" method="post" path="/api/partner/webhooks" -->
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
        Operations\WebhooksCreateEventRequest::CautionActivated,
        Operations\WebhooksCreateEventRequest::CautionStatusChanged,
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

<!-- UsageSnippet language="php" operationID="webhooks.delete" method="delete" path="/api/partner/webhooks/{id}" -->
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

<!-- UsageSnippet language="php" operationID="webhooks.update" method="patch" path="/api/partner/webhooks/{id}" -->
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
        Operations\WebhooksUpdateEventRequest::CautionActivated,
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
| `body`                                                                                                             | [Operations\UpdatePartnerWebhookSubscriptionBody](../../Models/Operations/UpdatePartnerWebhookSubscriptionBody.md) | :heavy_check_mark:                                                                                                 | Partner webhook endpoint update payload                                                                            | {<br/>"isActive": true,<br/>"events": [<br/>"rental_operator.linked",<br/>"caution.activated"<br/>]<br/>}          |

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

<!-- UsageSnippet language="php" operationID="webhooks.rotateSecret" method="post" path="/api/partner/webhooks/{id}/rotate-secret" -->
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

<!-- UsageSnippet language="php" operationID="webhooks.getSecret" method="get" path="/api/partner/webhooks/{id}/secret" -->
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

Sends a sample **`caution.activated`** payload to the endpoint URL. The endpoint must be subscribed to either `caution.activated` or the wildcard `caution.status_changed`.

Outbound payload schema reference: `#/components/schemas/WebhookCautionStatusChangedEvent` (see Models). The `data.rental_contract` field carries the partner-side contract id set by the rental operator.

Sample payload sent by this test and the production dispatcher:

```json
{
  "event": "caution.activated",
  "created_at": "2026-03-02T10:00:00.000Z",
  "data": {
    "id": "test_deposit_id",
    "reference": "GAN-TEST",
    "rental_contract": "CTR-TEST-2026",
    "status": "active",
    "previous_status": "pending",
    "amount_cents": 150000,
    "contract_start_at": null,
    "contract_end_at": null,
    "client": null,
    "partner_context": {
      "partner_id": "ptr_xxx",
      "partner_name": "Fleetee",
      "external_id": "fleet_operator_42"
    }
  }
}
```

### Example Usage

<!-- UsageSnippet language="php" operationID="webhooks.test" method="post" path="/api/partner/webhooks/{id}/test" -->
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

<!-- UsageSnippet language="php" operationID="webhooks.getDeliveries" method="get" path="/api/partner/webhooks/{id}/deliveries" -->
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