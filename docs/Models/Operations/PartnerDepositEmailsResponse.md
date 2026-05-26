# PartnerDepositEmailsResponse


## Fields

| Field                                                         | Type                                                          | Required                                                      | Description                                                   |
| ------------------------------------------------------------- | ------------------------------------------------------------- | ------------------------------------------------------------- | ------------------------------------------------------------- |
| `link`                                                        | *string*                                                      | :heavy_check_mark:                                            | Deposit completion URL sent to recipients                     |
| `results`                                                     | array<[Operations\Result](../../Models/Operations/Result.md)> | :heavy_check_mark:                                            | Per-recipient send outcome                                    |