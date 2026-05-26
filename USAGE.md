<!-- Start SDK Example Usage [usage] -->
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