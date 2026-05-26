# WebhooksTestResponseBody

Test delivery succeeded


## Fields

| Field                                                                              | Type                                                                               | Required                                                                           | Description                                                                        | Example                                                                            |
| ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| `success`                                                                          | *bool*                                                                             | :heavy_check_mark:                                                                 | Always `true` for successful responses                                             |                                                                                    |
| `data`                                                                             | [Operations\PartnerWebhookTestOk](../../Models/Operations/PartnerWebhookTestOk.md) | :heavy_check_mark:                                                                 | N/A                                                                                | {<br/>"statusCode": 200<br/>}                                                      |
| `message`                                                                          | *?string*                                                                          | :heavy_minus_sign:                                                                 | Optional human-readable message                                                    |                                                                                    |