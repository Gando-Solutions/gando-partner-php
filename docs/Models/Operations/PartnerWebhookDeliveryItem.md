# PartnerWebhookDeliveryItem


## Fields

| Field                                                         | Type                                                          | Required                                                      | Description                                                   |
| ------------------------------------------------------------- | ------------------------------------------------------------- | ------------------------------------------------------------- | ------------------------------------------------------------- |
| `id`                                                          | *string*                                                      | :heavy_check_mark:                                            | Webhook delivery unique identifier                            |
| `eventType`                                                   | [Operations\EventType](../../Models/Operations/EventType.md)  | :heavy_check_mark:                                            | Event type delivered                                          |
| `statusCode`                                                  | *int*                                                         | :heavy_check_mark:                                            | HTTP status code returned by your endpoint                    |
| `error`                                                       | *string*                                                      | :heavy_check_mark:                                            | Error message when delivery failed                            |
| `attemptCount`                                                | *int*                                                         | :heavy_check_mark:                                            | Delivery attempt number (1-based)                             |
| `deliveredAt`                                                 | [\DateTime](https://www.php.net/manual/en/class.datetime.php) | :heavy_check_mark:                                            | ISO 8601 timestamp when delivery succeeded                    |
| `nextRetryAt`                                                 | [\DateTime](https://www.php.net/manual/en/class.datetime.php) | :heavy_check_mark:                                            | ISO 8601 timestamp of the next retry, if scheduled            |
| `createdAt`                                                   | [\DateTime](https://www.php.net/manual/en/class.datetime.php) | :heavy_check_mark:                                            | Delivery record creation timestamp (ISO 8601)                 |