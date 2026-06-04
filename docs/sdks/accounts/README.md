# Accounts

## Overview

Rental operator account management — list linked accounts and revoke partner links.

### Available Operations

* [list](#list) - List linked rental operator accounts
* [revoke](#revoke) - Revoke partner ↔ rental operator link

## list

Returns rental operator accounts linked to your partner. Filter with `status`: `active` (default), `revoked`, or `all`.

### Example Usage

<!-- UsageSnippet language="php" operationID="accounts.list" method="get" path="/api/partner/v1/accounts" -->
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

Revokes the link between your partner and the given rental operator `accountId`. Further deposit operations for that account return **403** until re-linked.

### Example Usage

<!-- UsageSnippet language="php" operationID="accounts.revoke" method="delete" path="/api/partner/v1/accounts/{id}" -->
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