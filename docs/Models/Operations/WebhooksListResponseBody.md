# WebhooksListResponseBody

Paginated webhook endpoints (`items` + `total`)


## Fields

| Field                                                                                | Type                                                                                 | Required                                                                             | Description                                                                          |
| ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------ |
| `success`                                                                            | *bool*                                                                               | :heavy_check_mark:                                                                   | Always `true` for successful responses                                               |
| `data`                                                                               | [Operations\V1WebhookListResponse](../../Models/Operations/V1WebhookListResponse.md) | :heavy_check_mark:                                                                   | N/A                                                                                  |
| `message`                                                                            | *?string*                                                                            | :heavy_minus_sign:                                                                   | Optional human-readable message                                                      |