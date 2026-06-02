# Gando Partner PHP SDK

Official PHP SDK for the [Gando Partner API](https://developers.gando.app/partner) — integrate digital deposit guarantees into rental management software on behalf of linked rental operators.

**Requirements:** PHP 8.2+, Composer.

## Installation

```bash
composer require gando/partner-sdk
```

> **Note:** The Composer package is published as `gando/partner-sdk`. During early access you can also install from this repository:
>
> ```json
> {
>   "repositories": [{ "type": "vcs", "url": "https://github.com/Gando-Solutions/gando-partner-php" }],
>   "require": { "gando/partner-sdk": "dev-main" }
> }
> ```

## Quickstart

Create a deposit in a few lines. Set `GANDO_API_KEY` (`gando_pk_…`) and `GANDO_ACCOUNT_ID` (a linked rental operator account) in your environment.

```php
<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner\Api\Client;
use Gando\Partner\Models\Operations\PartnerCreateDepositBody;

$api = new Client(
    apiKey: getenv('GANDO_API_KEY'),
    baseUrl: 'https://gando.app',
);

$deposit = $api->deposits->create(new PartnerCreateDepositBody(
    accountId: getenv('GANDO_ACCOUNT_ID'),
    amount: 800.0,
    rentalContract: 'CTR-2026-042',
    contractStartAt: '2026-04-01T00:00:00.000Z',
    contractEndAt: '2026-04-10T23:59:59.000Z',
));

printf("Deposit %s created (status: %s)\n", $deposit->object->data->id, $deposit->object->data->status->value);
```

Add `inlineRedirect: true` and `returnUrl` to receive a `deposit_url` and send the tenant straight to Gando checkout. See [Deposits](docs/sdks/deposits/README.md#create) for the full request shape.

## Authentication

Gando partner integrations use **different secrets for different jobs**. Do not mix them.

| Secret | Prefix | Where to use | Purpose |
| --- | --- | --- | --- |
| Partner API key | `gando_pk_` | `Gando\Partner\Api\Client` / `Gando\Partner\Gando` | Call `/api/partner/*` (deposits, clients, accounts, webhooks) |
| Connect secret | `gando_cs_` | `Gando\Partner\Connect\UrlBuilder` | Sign rental-operator signup URLs (`/register`) |
| Webhook signing secret | `gando_whsec_` | `Gando\Partner\WebhookVerifier` | Verify inbound webhook deliveries |

### Where to get your keys

| Credential | How to obtain |
| --- | --- |
| `gando_pk_` | Issued by Gando when your partner integration is enabled. Send as `x-api-key: gando_pk_…` or `Authorization: Bearer gando_pk_…`. |
| `gando_cs_` | Issued alongside your partner slug for Connect onboarding. Never expose in browser-side code. |
| `gando_whsec_` | Returned **once** when you create a webhook endpoint via `POST /api/partner/webhooks`. Store it securely; rotate via the API if lost. |

**`gando_pk_` vs `gando_cs_`:** the API key authenticates server-to-server Partner API calls. The connect secret only signs onboarding URLs so Gando can trust that a rental operator was referred by your platform. They are not interchangeable.

```php
use Gando\Partner\Api\Client;
use Gando\Partner\Connect\UrlBuilder;

$api = new Client(apiKey: 'gando_pk_…', baseUrl: 'https://gando.app');

$signupUrl = (new UrlBuilder(
    connectSecret: 'gando_cs_…',
    partnerSlug: 'your-partner-slug',
    baseUrl: 'https://dashboard.gando.app',
))->signupUrl(externalId: 'your_rental_operator_ref');
```

## Configuration

### Recommended entry point: `Gando\Partner\Api\Client`

```php
use Gando\Partner\Api\Client;
use GuzzleHttp\Client as GuzzleClient;

$api = new Client(
    apiKey: getenv('GANDO_API_KEY'),
    httpClient: new GuzzleClient(['timeout' => 30]), // request timeout in seconds (default: 60 via SDK builder)
    baseUrl: 'https://gando.app',                     // see environments below
);
```

### Base URL (staging vs production)

| Environment | API base URL | Dashboard (Connect URLs) | API docs |
| --- | --- | --- | --- |
| Production | `https://gando.app` | `https://dashboard.gando.app` | [developers.gando.app/partner](https://developers.gando.app/partner) |
| Staging | `https://staging.gando.app` | `https://dashboard.gando.app` | [developers-staging.gando.app/partner](https://developers-staging.gando.app/partner) |
| Local dev | `http://localhost:3000` | `http://localhost:3000` | — |

The generated SDK defaults to `http://localhost:3000`. **Always set `baseUrl` explicitly** in staging and production.

### Advanced: `Gando::builder()`

For full control (global retry policy, custom Guzzle stack):

```php
use Gando\Partner\Gando;
use Gando\Partner\Models\Components\Security;
use Gando\Partner\Utils\Retry\RetryConfigBackoff;

$sdk = Gando::builder()
    ->setServerUrl('https://gando.app')
    ->setSecurity(new Security(partnerApiKeyAuth: getenv('GANDO_API_KEY')))
    ->setRetryConfig(new RetryConfigBackoff(
        initialInterval: 500,      // ms
        maxInterval: 60_000,       // ms
        exponent: 1.5,
        maxElapsedTime: 30_000,    // ms — total retry window
        retryConnectionErrors: true,
    ))
    ->build();
```

### Default retry policy

All Partner API operations retry transient failures automatically:

| Setting | Default |
| --- | --- |
| Strategy | Exponential backoff (500 ms → 60 s, exponent 1.5) |
| Max elapsed time | 30 s |
| Max attempts | 3 (1 initial + 2 retries) |
| Retried status codes | `429`, `5xx` |
| Connection errors | Retried |

The SDK honours a `Retry-After` response header when present. Override globally via `setRetryConfig()` or per call via `Gando\Partner\Utils\Options`.

## Webhook verification

Verify every inbound delivery **before** parsing JSON. Use the raw request body — not `json_decode` output.

**Headers:** `X-Gando-Signature` (`sha256=<hex>`), `X-Gando-Timestamp` (Unix seconds), `X-Gando-Event` (event name).

```php
<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Gando\Partner\Exceptions\WebhookSignatureException;
use Gando\Partner\WebhookVerifier;

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_GANDO_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_GANDO_TIMESTAMP'] ?? '';
$event = $_SERVER['HTTP_X_GANDO_EVENT'] ?? '';
$secret = getenv('GANDO_WEBHOOK_SECRET'); // gando_whsec_… from endpoint creation

if ($secret === false || $secret === '') {
    http_response_code(500);
    exit;
}

try {
    WebhookVerifier::verify($rawBody, $signature, $timestamp, $secret);
} catch (WebhookSignatureException $e) {
    // $e->getReason() is "invalid" or "expired"
    http_response_code(400);
    exit;
}

$payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);

// Acknowledge quickly; process $event / $payload asynchronously.
http_response_code(200);
```

See also [Webhooks SDK docs](docs/sdks/webhooks/README.md) and [`recipes/snippets/webhooks.verify.php`](recipes/snippets/webhooks.verify.php).

## Idempotency

`POST /api/partner/deposits` (and other mutating POST routes) accept an optional `Idempotency-Key` header (UUID v4). Within **24 hours**, the same key + same JSON body replays the cached response; same key + different body returns **409** `idempotency_key_conflict`.

**Default behaviour (`Gando\Partner\Api\Deposits`):**

- When you omit `idempotencyKey` on `deposits->create()`, the SDK generates a UUID v4 and sends it on **every HTTP attempt** for that call — including retries — so a transient `429`/`5xx` does not create duplicate deposits.
- With a PSR-16 cache injected into `Client`, the same key is reused for identical request bodies within 24 h (useful when your app retries at a higher layer).

**Override:**

```php
$deposit = $api->deposits->create(
    body: $body,
    idempotencyKey: '550e8400-e29b-41d4-a716-446655440000', // your UUID v4
);
```

Pass your own key when you need cross-process deduplication tied to your booking or contract ID (store and reuse the UUID per logical operation).

## Pagination

List endpoints (`accounts->list()`, `deposits->list()`, `clients->list()`, `webhooks->list()`) return **one page per call**. Pass **`page`** (1-based) and **`limit`** on each request; use response metadata (`total`, `numPages`, etc.) to decide whether to fetch the next page.

```php
use Gando\Partner\Api\Client;
use Gando\Partner\Models\Operations\DepositsListRequest;

$api = new Client(apiKey: getenv('GANDO_API_KEY'), baseUrl: 'https://gando.app');

$page = 1;
do {
    $response = $api->deposits->list(new DepositsListRequest(
        accountId: getenv('GANDO_ACCOUNT_ID'),
        page: $page,
        limit: 50,
    ));
    foreach ($response->object->data->items as $deposit) {
        echo $deposit->id, ' ', $deposit->status->value, PHP_EOL;
    }
    $page++;
} while ($page <= ($response->object->data->numPages ?? $page));
```

## Error handling

Operations either return a typed response or throw. Catch structured API errors separately from unexpected failures.

| Exception | When | Useful fields |
| --- | --- | --- |
| `Gando\Partner\Models\Errors\ErrorEnvelopeThrowable` | Documented 4xx/5xx with JSON `ErrorEnvelope` | `$e->container->error->code`, `->message`, `->requestId` |
| `Gando\Partner\Models\Errors\APIException` | Undocumented or non-JSON errors | `$e->statusCode`, `$e->body`, `$e->rawResponse` |
| `Gando\Partner\Exceptions\WebhookSignatureException` | Webhook HMAC verification failed | `$e->getReason()` → `invalid` or `expired` |

Common `error.code` values:

| HTTP | Code | Meaning |
| --- | --- | --- |
| 401 | `missing_api_key`, `invalid_api_key`, `api_key_revoked` | Partner key missing, wrong, or revoked |
| 403 | `account_not_linked`, `deposit_access_denied` | Rental operator not linked to your partner |
| 404 | `deposit_not_found`, `client_not_found` | Resource does not exist |
| 409 | `idempotency_key_conflict` | Idempotency key reused with a different body |
| 429 | `rate_limited` | Rate limit exceeded — SDK retries automatically |
| 503 | `redis_unavailable` | Idempotency store unavailable |

```php
use Gando\Partner\Api\Client;
use Gando\Partner\Models\Errors\APIException;
use Gando\Partner\Models\Errors\ErrorEnvelopeThrowable;

$api = new Client(apiKey: getenv('GANDO_API_KEY'), baseUrl: 'https://gando.app');

try {
    $deposit = $api->deposits->retrieve('dep_unknown');
} catch (ErrorEnvelopeThrowable $e) {
    $err = $e->container->error;
    fprintf(STDERR, "[%s] %s (requestId: %s)\n", $err->code->value, $err->message, $err->requestId);
} catch (APIException $e) {
    fprintf(STDERR, "HTTP %d: %s\n", $e->statusCode, $e->body);
}
```

Include `requestId` in support tickets — Gando uses it to trace your request in logs.

## Links

| Resource | URL |
| --- | --- |
| Partner API reference (Scalar) | [developers.gando.app/partner](https://developers.gando.app/partner) |
| Recipes (copy-paste snippets) | [recipes/snippets/](recipes/snippets/) · [GitHub](https://github.com/Gando-Solutions/gando-partner-php/tree/main/recipes/snippets) |
| Postman / API client | Import the OpenAPI spec from [developers.gando.app/partner](https://developers.gando.app/partner) (Scalar → Export → Postman) |
| SDK operation docs | [docs/sdks/](docs/sdks/) |
| Connect URL builder | [docs/sdks/connect/README.md](docs/sdks/connect/README.md) |
| Support portal | [support.gando.app](https://support.gando.app/introduction) |

## Versioning

This SDK follows [Semantic Versioning](https://semver.org/).

- **No-break policy:** minor and patch releases do not remove or change existing public method signatures, request/response model fields, or enum values without a major version bump.
- **Deprecation:** features marked deprecated remain functional for at least **3 months** before removal in the next major release. Deprecated APIs are documented in release notes and annotated in PHPDoc.
- **Pin your version** in `composer.json` (e.g. `"gando/partner-sdk": "^1.0"`) and read the [GitHub Releases](https://github.com/Gando-Solutions/gando-partner-php/releases) before upgrading.

The Speakeasy-generated core (`src/Gando.php`, `src/Deposits.php`, …) is regenerated from the Partner OpenAPI spec. Hand-written extensions (`src/Api/`, `src/Connect/`, `src/WebhookVerifier.php`) follow the same SemVer guarantees.

## Support

- **Email:** [contact@gando.app](mailto:contact@gando.app)
- **Issues:** [github.com/Gando-Solutions/gando-partner-php/issues](https://github.com/Gando-Solutions/gando-partner-php/issues)

When reporting a bug, include the SDK version, PHP version, `requestId` from the error envelope (if any), and steps to reproduce.

---

Built for [Gando](https://gando.app) · [MIT](LICENSE)
