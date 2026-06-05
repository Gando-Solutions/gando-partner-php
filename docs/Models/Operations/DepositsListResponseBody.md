# DepositsListResponseBody

Paginated list (`items` + `total`; optional `counts`)


## Fields

| Field                                                                                | Type                                                                                 | Required                                                                             | Description                                                                          |
| ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------ |
| `success`                                                                            | *bool*                                                                               | :heavy_check_mark:                                                                   | Always `true` for successful responses                                               |
| `data`                                                                               | [Operations\V1DepositListResponse](../../Models/Operations/V1DepositListResponse.md) | :heavy_check_mark:                                                                   | N/A                                                                                  |
| `message`                                                                            | *?string*                                                                            | :heavy_minus_sign:                                                                   | Optional human-readable message                                                      |