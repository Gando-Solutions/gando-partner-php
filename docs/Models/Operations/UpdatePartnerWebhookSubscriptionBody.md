# UpdatePartnerWebhookSubscriptionBody

Partner webhook endpoint update payload


## Fields

| Field                                                                                                 | Type                                                                                                  | Required                                                                                              | Description                                                                                           |
| ----------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `url`                                                                                                 | *?string*                                                                                             | :heavy_minus_sign:                                                                                    | Destination URL (must start with http:// or https://)                                                 |
| `events`                                                                                              | array<[Operations\WebhooksUpdateEventRequest](../../Models/Operations/WebhooksUpdateEventRequest.md)> | :heavy_minus_sign:                                                                                    | Replace the subscribed event list                                                                     |
| `isActive`                                                                                            | *?bool*                                                                                               | :heavy_minus_sign:                                                                                    | Enable or disable deliveries for this endpoint                                                        |