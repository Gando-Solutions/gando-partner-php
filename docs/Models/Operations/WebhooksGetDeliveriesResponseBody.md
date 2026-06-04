# WebhooksGetDeliveriesResponseBody

Delivery history


## Fields

| Field                                                                                       | Type                                                                                        | Required                                                                                    | Description                                                                                 |
| ------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| `success`                                                                                   | *bool*                                                                                      | :heavy_check_mark:                                                                          | Always `true` for successful responses                                                      |
| `data`                                                                                      | array<[Operations\V1WebhookDeliveryItem](../../Models/Operations/V1WebhookDeliveryItem.md)> | :heavy_check_mark:                                                                          | Recent webhook delivery attempts for this endpoint                                          |
| `message`                                                                                   | *?string*                                                                                   | :heavy_minus_sign:                                                                          | Optional human-readable message                                                             |