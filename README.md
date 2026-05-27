# gando/partner

Developer-friendly & type-safe Php SDK specifically catered to leverage *gando/partner* API.

[![Built by Speakeasy](https://img.shields.io/badge/Built_by-SPEAKEASY-374151?style=for-the-badge&labelColor=f3f4f6)](https://www.speakeasy.com/?utm_source=gando/partner&utm_campaign=php)
[![License: MIT](https://img.shields.io/badge/LICENSE_//_MIT-3b5bdb?style=for-the-badge&labelColor=eff6ff)](https://opensource.org/licenses/MIT)


<br /><br />
> [!IMPORTANT]
> This SDK is not yet ready for production use. To complete setup please follow the steps outlined in your [workspace](https://app.speakeasy.com/org/gando/gando). Delete this section before > publishing to a package manager.

<!-- Start Summary [summary] -->
## Summary

Gando Partner API: API for **rental management software** and **multi–rental-operator platforms** integrating Gando on behalf of linked rental operators. Use **`gando_pk_`** keys (`x-api-key` or `Authorization: Bearer`) on `/api/partner/*`.
<!-- End Summary [summary] -->

<!-- Start Table of Contents [toc] -->
## Table of Contents
<!-- $toc-max-depth=2 -->
* [gando/partner](#gandopartner)
  * [SDK Installation](#sdk-installation)
  * [SDK Example Usage](#sdk-example-usage)
  * [Authentication](#authentication)
  * [Available Resources and Operations](#available-resources-and-operations)
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
* [getCapture](docs/sdks/deposits/README.md#getcapture) - Get latest capture for a deposit
* [capture](docs/sdks/deposits/README.md#capture) - Create a capture (encaissement)
* [sendEmails](docs/sdks/deposits/README.md#sendemails) - Send deposit link to multiple emails
* [sendDepositMail](docs/sdks/deposits/README.md#senddepositmail) - Send deposit link to one email
* [cancel](docs/sdks/deposits/README.md#cancel) - Close deposit (status close + optional email)
* [getPaymentMethod](docs/sdks/deposits/README.md#getpaymentmethod) - Masked card info for the deposit
* [getPdf](docs/sdks/deposits/README.md#getpdf) - Download deposit summary PDF

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
