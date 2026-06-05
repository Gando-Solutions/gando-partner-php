# gando/partner

Developer-friendly & type-safe Php SDK specifically catered to leverage *gando/partner* API.

[![Built by Speakeasy](https://img.shields.io/badge/Built_by-SPEAKEASY-374151?style=for-the-badge&labelColor=f3f4f6)](https://www.speakeasy.com/?utm_source=gando/partner&utm_campaign=php)
[![License: MIT](https://img.shields.io/badge/LICENSE_//_MIT-3b5bdb?style=for-the-badge&labelColor=eff6ff)](https://opensource.org/licenses/MIT)


<br /><br />
> [!IMPORTANT]
> This SDK is not yet ready for production use. To complete setup please follow the steps outlined in your [workspace](https://app.speakeasy.com/org/gando/gando). Delete this section before > publishing to a package manager.

<!-- Start Summary [summary] -->
## Summary

Gando Partner API v1: API for **rental management software** and **multi–rental-operator platforms** integrating Gando on behalf of linked rental operators. Use **`gando_pk_`** keys (`x-api-key` or `Authorization: Bearer`) on `/api/partner/v1/*`.
<!-- End Summary [summary] -->

<!-- Start Table of Contents [toc] -->
## Table of Contents
<!-- $toc-max-depth=2 -->
* [gando/partner](#gandopartner)
  * [SDK Installation](#sdk-installation)
  * [Two credentials, two classes](#two-credentials-two-classes)
  * [SDK Example Usage](#sdk-example-usage)
  * [Authentication](#authentication)
  * [Available Resources and Operations](#available-resources-and-operations)
  * [Default retry policy](#default-retry-policy)
  * [Retries](#retries)
  * [Error Handling](#error-handling)
  * [Server Selection](#server-selection)
  * [Webhook signature verification](#webhook-signature-verification)
* [Development](#development)
  * [Maturity](#maturity)
  * [Contributions](#contributions)

<!-- End Table of Contents [toc] -->

<!-- Start SDK Installation [installation] -->
## SDK Installation

The SDK relies on [Composer](https://getcomposer.org/) to manage its dependencies.

To install the SDK and add it as a dependency to an existing `composer.json` file:
```bash
composer require "gando/partner"
```
<!-- End SDK Installation [installation] -->

## Two credentials, two classes

Gando Partner integrations use **two different secrets** depending on what you are doing.

| Secret | Prefix | Class | Use |
| --- | --- | --- | --- |
| Partner API key | `gando_pk_` | `Gando\Partner\Api\Client` | Call `/api/partner/*` |
| Connect secret | `gando_cs_` | [`Gando\Partner\Connect\UrlBuilder`](docs/sdks/connect/README.md) | Build signed `/register` URLs |
| Webhook secret | `gando_whsec_` | `Gando\Partner\WebhookVerifier` | Verify inbound webhooks |

### Example

```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner\Api\Client;
use Gando\Partner\Connect\UrlBuilder;

$api = new Client(apiKey: getenv('GANDO_API_KEY') ?: 'gando_pk_...');
$response = $api->accounts->list();

$builder = new UrlBuilder(
    connectSecret: getenv('GANDO_CONNECT_SECRET') ?: 'gando_cs_...',
    partnerSlug: 'fleetee',
    baseUrl: 'https://dashboard.gando.app',
);
$signupUrl = $builder->signupUrl(externalId: 'fleet_acct_42');
```

### PSR injection (enterprise/Symfony)

`Gando\Partner\Api\Client` accepts optional PSR interfaces so you can reuse your existing stack:

- PSR-18: `Psr\Http\Client\ClientInterface`
- PSR-17: `Psr\Http\Message\RequestFactoryInterface`
- PSR-3: `Psr\Log\LoggerInterface`
- PSR-16: `Psr\SimpleCache\CacheInterface`
- PSR-14: `Psr\EventDispatcher\EventDispatcherInterface`

```php
declare(strict_types=1);

use Gando\Partner\Api\Client;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/** @var HttpClientInterface $httpClient */
/** @var RequestFactoryInterface $requestFactory */
/** @var LoggerInterface $logger */
/** @var CacheInterface $cache */
/** @var EventDispatcherInterface $events */

$api = new Client(
    apiKey: $_ENV['GANDO_API_KEY'],
    httpClient: $httpClient,
    requestFactory: $requestFactory,
    logger: $logger,
    cache: $cache,
    events: $events,
);
```

All PSR dependencies are optional. If you omit `httpClient` and `requestFactory`, the SDK auto-discovers implementations through `php-http/discovery` (works out-of-the-box when `guzzlehttp/guzzle` v7 is installed).

### Deposit create idempotency

`POST /api/partner/deposits` is idempotent when the `Idempotency-Key` header is sent (UUID v4, 24h deduplication via Redis on the API). **`Gando\Partner\Api\Client`** auto-generates that key on `deposits->create()` when you omit it, so SDK retries do not create duplicate deposits. Pass your own key to override.

<!-- Start SDK Example Usage [usage] -->
## SDK Example Usage

### Example

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



$response = $sdk->accounts->list(
    page: 1,
    limit: 20

);

if ($response->object !== null) {
    // handle response
}
```
<!-- End SDK Example Usage [usage] -->

<!-- Start Authentication [security] -->
## Authentication

### Per-Client Security Schemes

This SDK supports the following security schemes globally:

| Name                | Type   | Scheme      |
| ------------------- | ------ | ----------- |
| `partnerApiKeyAuth` | apiKey | API key     |
| `partnerBearerAuth` | http   | HTTP Bearer |

You can set the security parameters through the `setSecurity` function on the `SDKBuilder` when initializing the SDK. The selected scheme will be used by default to authenticate with the API for all operations that support it. For example:
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



$response = $sdk->accounts->list(
    page: 1,
    limit: 20

);

if ($response->object !== null) {
    // handle response
}
```
<!-- End Authentication [security] -->

<!-- Start Available Resources and Operations [operations] -->
## Available Resources and Operations

<details open>
<summary>Available methods</summary>

### [Accounts](docs/sdks/accounts/README.md)

* [list](docs/sdks/accounts/README.md#list) - List linked rental operator accounts
* [revoke](docs/sdks/accounts/README.md#revoke) - Revoke partner ↔ rental operator link

### [Clients](docs/sdks/clients/README.md)

* [list](docs/sdks/clients/README.md#list) - List clients across linked rental operator accounts
* [create](docs/sdks/clients/README.md#create) - Create a client for a linked rental operator account
* [update](docs/sdks/clients/README.md#update) - Update a partner-accessible client

### [Deposits](docs/sdks/deposits/README.md)

* [list](docs/sdks/deposits/README.md#list) - List deposits
* [create](docs/sdks/deposits/README.md#create) - Create a deposit for a linked rental operator
* [retrieve](docs/sdks/deposits/README.md#retrieve) - Get deposit by id
* [delete](docs/sdks/deposits/README.md#delete) - Delete or archive a deposit
* [update](docs/sdks/deposits/README.md#update) - Update deposit (change client or cancel pending payment)
* [cancel](docs/sdks/deposits/README.md#cancel) - Close deposit (status close + optional email)
* [getCapture](docs/sdks/deposits/README.md#getcapture) - Get latest capture for a deposit
* [capture](docs/sdks/deposits/README.md#capture) - Create a capture (encaissement)
* [sendEmails](docs/sdks/deposits/README.md#sendemails) - Send deposit link to multiple emails
* [sendDepositMail](docs/sdks/deposits/README.md#senddepositmail) - Send deposit link to one email
* [getPaymentMethod](docs/sdks/deposits/README.md#getpaymentmethod) - Masked card info for the deposit
* [getPdf](docs/sdks/deposits/README.md#getpdf) - Download deposit summary PDF
* [depositsGetScoring](docs/sdks/deposits/README.md#depositsgetscoring) - Latest open-banking scoring for the deposit client

### [Webhooks](docs/sdks/webhooks/README.md)

* [list](docs/sdks/webhooks/README.md#list) - List partner webhook endpoints
* [create](docs/sdks/webhooks/README.md#create) - Create partner webhook endpoint
* [delete](docs/sdks/webhooks/README.md#delete) - Delete partner webhook endpoint
* [update](docs/sdks/webhooks/README.md#update) - Update partner webhook endpoint
* [rotateSecret](docs/sdks/webhooks/README.md#rotatesecret) - Rotate partner webhook secret
* [getSecret](docs/sdks/webhooks/README.md#getsecret) - Get partner webhook secret
* [test](docs/sdks/webhooks/README.md#test) - Send test partner webhook delivery
* [getDeliveries](docs/sdks/webhooks/README.md#getdeliveries) - List partner webhook deliveries

</details>
<!-- End Available Resources and Operations [operations] -->

## Default retry policy

All Partner API operations use the global `x-speakeasy-retries` extension from the Partner OpenAPI document (`PARTNER_SPEAKEASY_RETRIES` in `gando-app` → `lib/api/openapi/shared.ts`). Out of the box, the SDK retries transient failures without custom loops in partner integrations.

| Setting | Value |
| --- | --- |
| Strategy | Exponential backoff (`initialInterval` 500 ms, `maxInterval` 60 s, `exponent` 1.5) |
| Max elapsed time | 30 s |
| Status codes | `429`, `5xx` (server errors) |
| Connection errors | Retried when enabled |
| Attempts | Up to **3** (1 initial + **2** retries) within the elapsed window |

The SDK respects a `Retry-After` response header when present. Override globally via `Gando::builder()->setRetryConfig(...)` or per call via `Utils\Options` (see below).

<!-- Start Retries [retries] -->
## Retries

Some of the endpoints in this SDK support retries. If you use the SDK without any configuration, it will fall back to the default retry strategy provided by the API. However, the default retry strategy can be overridden on a per-operation basis, or across the entire SDK.

To change the default retry strategy for a single API call, simply provide an `Options` object built with a `RetryConfig` object to the call:
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;
use Gando\Partner\Utils\Retry;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();



$response = $sdk->accounts->list(
    page: 1,
    limit: 20,
    options: Utils\Options->builder()->setRetryConfig(
        new Retry\RetryConfigBackoff(
            initialInterval: 1,
            maxInterval:     50,
            exponent:        1.1,
            maxElapsedTime:  100,
            retryConnectionErrors: false,
        ))->build()

);

if ($response->object !== null) {
    // handle response
}
```

If you'd like to override the default retry strategy for all operations that support retries, you can pass a `RetryConfig` object to the `SDKBuilder->setRetryConfig` function when initializing the SDK:
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;
use Gando\Partner\Utils\Retry;

$sdk = Partner\Gando::builder()
    ->setRetryConfig(
        new Retry\RetryConfigBackoff(
            initialInterval: 1,
            maxInterval:     50,
            exponent:        1.1,
            maxElapsedTime:  100,
            retryConnectionErrors: false,
        )
  )
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();



$response = $sdk->accounts->list(
    page: 1,
    limit: 20

);

if ($response->object !== null) {
    // handle response
}
```
<!-- End Retries [retries] -->

<!-- Start Error Handling [errors] -->
## Error Handling

Handling errors in this SDK should largely match your expectations. All operations return a response object or throw an exception.

By default an API error will raise a `Errors\APIException` exception, which has the following properties:

| Property       | Type                                    | Description           |
|----------------|-----------------------------------------|-----------------------|
| `$message`     | *string*                                | The error message     |
| `$statusCode`  | *int*                                   | The HTTP status code  |
| `$rawResponse` | *?\Psr\Http\Message\ResponseInterface*  | The raw HTTP response |
| `$body`        | *string*                                | The response content  |

When custom error responses are specified for an operation, the SDK may also throw their associated exception. You can refer to respective *Errors* tables in SDK docs for more details on possible exception types for each operation. For example, the `list` method throws the following exceptions:

| Error Type           | Status Code                       | Content Type     |
| -------------------- | --------------------------------- | ---------------- |
| Errors\ErrorEnvelope | 400, 401, 403, 404, 409, 422, 429 | application/json |
| Errors\ErrorEnvelope | 500                               | application/json |
| Errors\APIException  | 4XX, 5XX                          | \*/\*            |

### Example

```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;
use Gando\Partner\Models\Errors;

$sdk = Partner\Gando::builder()
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();

try {
    $response = $sdk->accounts->list(
        page: 1,
        limit: 20

    );

    if ($response->object !== null) {
        // handle response
    }
} catch (Errors\ErrorEnvelopeThrowable $e) {
    // handle $e->$container data
    throw $e;
} catch (Errors\ErrorEnvelopeThrowable $e) {
    // handle $e->$container data
    throw $e;
} catch (Errors\APIException $e) {
    // handle default exception
    throw $e;
}
```
<!-- End Error Handling [errors] -->

<!-- Start Server Selection [server] -->
## Server Selection

### Override Server URL Per-Client

The default server can be overridden globally using the `setServerUrl(string $serverUrl)` builder method when initializing the SDK client instance. For example:
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner;
use Gando\Partner\Models\Components;

$sdk = Partner\Gando::builder()
    ->setServerURL('http://localhost:3000')
    ->setSecurity(
        new Components\Security(
            partnerApiKeyAuth: '<YOUR_API_KEY_HERE>',
        )
    )
    ->build();



$response = $sdk->accounts->list(
    page: 1,
    limit: 20

);

if ($response->object !== null) {
    // handle response
}
```
<!-- End Server Selection [server] -->

<!-- Placeholder for Future Speakeasy SDK Sections -->

## Webhook signature verification

Inbound partner webhooks are signed by Gando. Verify every delivery before processing the JSON payload.

**Headers:** `X-Gando-Signature` (`sha256=<hex>`), `X-Gando-Timestamp` (Unix seconds), `X-Gando-Event` (event name).

Use the raw request body (not `json_decode` output). The signing secret is returned once when you [create a webhook endpoint](docs/sdks/webhooks/README.md#create) (`gando_whsec_...`).

```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner\Exceptions\WebhookSignatureException;
use Gando\Partner\WebhookVerifier;

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_GANDO_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_GANDO_TIMESTAMP'] ?? '';
$secret = getenv('GANDO_WEBHOOK_SECRET'); // your signing secret

try {
    WebhookVerifier::verify($rawBody, $signature, $timestamp, $secret);
} catch (WebhookSignatureException $e) {
    // $e->getReason() is "invalid" or "expired"
    http_response_code(400);
    exit;
}

$payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
// handle $payload...
```

See also [Webhooks SDK docs](docs/sdks/webhooks/README.md) and the recipe snippet at `recipes/snippets/webhooks.verify.php`.

# Development

## Maturity

This SDK is in beta, and there may be breaking changes between versions without a major version update. Therefore, we recommend pinning usage
to a specific package version. This way, you can install the same version each time without breaking changes unless you are intentionally
looking for the latest version.

## Contributions

While we value open-source contributions to this SDK, this library is generated programmatically. Any manual changes added to internal files will be overwritten on the next generation. 
We look forward to hearing your feedback. Feel free to open a PR or an issue with a proof of concept and we'll do our best to include it in a future release. 

### SDK Created by [Speakeasy](https://www.speakeasy.com/?utm_source=gando/partner&utm_campaign=php)
