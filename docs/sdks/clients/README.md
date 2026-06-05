# Clients

## Overview

Client management endpoints — create, list, retrieve and update clients.

### Available Operations

* [list](#list) - List clients across linked rental operator accounts
* [create](#create) - Create a client for a linked rental operator account
* [update](#update) - Update a partner-accessible client

## list

Returns paginated clients for all active linked rental operator accounts, or for a specific linked account when `accountId` query is provided.

### Example Usage

<!-- UsageSnippet language="php" operationID="clients.list" method="get" path="/api/partner/v1/clients" -->
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



$response = $sdk->clients->list(
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

## create

Creates a client and returns its id. This id can then be sent as optional `clientId` in `POST /api/partner/v1/deposits`. This endpoint is idempotent by email within account: when a client already exists, it returns 200 with the existing id.

### Example Usage

<!-- UsageSnippet language="php" operationID="clients.create" method="post" path="/api/partner/v1/clients" -->
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



$response = $sdk->clients->create(
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

## update

Updates a client that belongs to one of the partner's linked rental operator accounts.

### Example Usage

<!-- UsageSnippet language="php" operationID="clients.update" method="patch" path="/api/partner/v1/clients/{id}" -->
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



$response = $sdk->clients->update(
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