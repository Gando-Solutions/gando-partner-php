# Clients

## Overview

Create and manage end-customer (tenant) records across linked rental operator accounts.

### Available Operations

* [clientsList](#clientslist) - List clients across linked rental operator accounts
* [clientsCreate](#clientscreate) - Create a client for a linked rental operator account
* [clientsUpdate](#clientsupdate) - Update a partner-accessible client

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



$response = $sdk->clients->clientsList(
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



$response = $sdk->clients->clientsCreate(
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



$response = $sdk->clients->clientsUpdate(
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