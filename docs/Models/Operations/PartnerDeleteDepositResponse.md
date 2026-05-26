# PartnerDeleteDepositResponse

Deleted or archived


## Fields

| Field                                                        | Type                                                         | Required                                                     | Description                                                  |
| ------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| `success`                                                    | *bool*                                                       | :heavy_check_mark:                                           | Always true on success                                       |
| `message`                                                    | [Operations\Message](../../Models/Operations/Message.md)     | :heavy_check_mark:                                           | `Deleted` when removed; `Archived` when soft-deleted instead |