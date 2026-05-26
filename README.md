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
* [Development](#development)
  * [Maturity](#maturity)
  * [Contributions](#contributions)

<!-- End Table of Contents [toc] -->

<!-- Start SDK Installation [installation] -->
## SDK Installation

> [!TIP]
> To finish publishing your SDK you must [run your first generation action](https://www.speakeasy.com/docs/github-setup#step-by-step-guide).


The SDK relies on [Composer](https://getcomposer.org/) to manage its dependencies.

To install the SDK first add the below to your `composer.json` file:

```json
{
    "repositories": [
        {
            "type": "github",
            "url": "<UNSET>.git"
        }
    ],
    "require": {
        "gando/partner": "*"
    }
}
```

Then run the following command:

```bash
composer update
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



$response = $sdk->partnerAPI->accountsList(

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



$response = $sdk->partnerAPI->accountsList(

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

### [PartnerAPI](docs/sdks/partnerapi/README.md)

* [accountsList](docs/sdks/partnerapi/README.md#accountslist) - List linked rental operator accounts
* [accountsRevoke](docs/sdks/partnerapi/README.md#accountsrevoke) - Revoke partner ↔ rental operator link
* [clientsList](docs/sdks/partnerapi/README.md#clientslist) - List clients across linked rental operator accounts
* [clientsCreate](docs/sdks/partnerapi/README.md#clientscreate) - Create a client for a linked rental operator account
* [clientsUpdate](docs/sdks/partnerapi/README.md#clientsupdate) - Update a partner-accessible client
* [depositsList](docs/sdks/partnerapi/README.md#depositslist) - List deposits
* [depositsCreate](docs/sdks/partnerapi/README.md#depositscreate) - Create a deposit for a linked rental operator
* [depositsRetrieve](docs/sdks/partnerapi/README.md#depositsretrieve) - Get deposit by id
* [depositsDelete](docs/sdks/partnerapi/README.md#depositsdelete) - Delete or archive a deposit
* [depositsUpdate](docs/sdks/partnerapi/README.md#depositsupdate) - Update deposit (change client or cancel pending payment)
* [depositsGetCapture](docs/sdks/partnerapi/README.md#depositsgetcapture) - Get latest capture for a deposit
* [depositsCapture](docs/sdks/partnerapi/README.md#depositscapture) - Create a capture (encaissement)
* [depositsSendEmails](docs/sdks/partnerapi/README.md#depositssendemails) - Send deposit link to multiple emails
* [depositsSendDepositMail](docs/sdks/partnerapi/README.md#depositssenddepositmail) - Send deposit link to one email
* [depositsCancel](docs/sdks/partnerapi/README.md#depositscancel) - Close deposit (status close + optional email)
* [depositsGetPaymentMethod](docs/sdks/partnerapi/README.md#depositsgetpaymentmethod) - Masked card info for the deposit
* [depositsGetPdf](docs/sdks/partnerapi/README.md#depositsgetpdf) - Download deposit summary PDF

### [PartnerWebhooks](docs/sdks/partnerwebhooks/README.md)

* [webhooksList](docs/sdks/partnerwebhooks/README.md#webhookslist) - List partner webhook endpoints
* [webhooksCreate](docs/sdks/partnerwebhooks/README.md#webhookscreate) - Create partner webhook endpoint
* [webhooksDelete](docs/sdks/partnerwebhooks/README.md#webhooksdelete) - Delete partner webhook endpoint
* [webhooksUpdate](docs/sdks/partnerwebhooks/README.md#webhooksupdate) - Update partner webhook endpoint
* [webhooksRotateSecret](docs/sdks/partnerwebhooks/README.md#webhooksrotatesecret) - Rotate partner webhook secret
* [webhooksGetSecret](docs/sdks/partnerwebhooks/README.md#webhooksgetsecret) - Get partner webhook secret
* [webhooksTest](docs/sdks/partnerwebhooks/README.md#webhookstest) - Send test partner webhook delivery
* [webhooksGetDeliveries](docs/sdks/partnerwebhooks/README.md#webhooksgetdeliveries) - List partner webhook deliveries

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

When custom error responses are specified for an operation, the SDK may also throw their associated exception. You can refer to respective *Errors* tables in SDK docs for more details on possible exception types for each operation. For example, the `accountsList` method throws the following exceptions:

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
    $response = $sdk->partnerAPI->accountsList(

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



$response = $sdk->partnerAPI->accountsList(

);

if ($response->object !== null) {
    // handle response
}
```
<!-- End Server Selection [server] -->

<!-- Placeholder for Future Speakeasy SDK Sections -->

# Development

## Maturity

This SDK is in beta, and there may be breaking changes between versions without a major version update. Therefore, we recommend pinning usage
to a specific package version. This way, you can install the same version each time without breaking changes unless you are intentionally
looking for the latest version.

## Contributions

While we value open-source contributions to this SDK, this library is generated programmatically. Any manual changes added to internal files will be overwritten on the next generation. 
We look forward to hearing your feedback. Feel free to open a PR or an issue with a proof of concept and we'll do our best to include it in a future release. 

### SDK Created by [Speakeasy](https://www.speakeasy.com/?utm_source=gando/partner&utm_campaign=php)
