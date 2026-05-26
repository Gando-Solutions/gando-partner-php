# DepositsRetrieveResponseBody

Deposit details


## Fields

| Field                                                                          | Type                                                                           | Required                                                                       | Description                                                                    |
| ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------ |
| `success`                                                                      | *bool*                                                                         | :heavy_check_mark:                                                             | Always `true` for successful responses                                         |
| `data`                                                                         | [Operations\PartnerCautionItem](../../Models/Operations/PartnerCautionItem.md) | :heavy_check_mark:                                                             | N/A                                                                            |
| `message`                                                                      | *?string*                                                                      | :heavy_minus_sign:                                                             | Optional human-readable message                                                |