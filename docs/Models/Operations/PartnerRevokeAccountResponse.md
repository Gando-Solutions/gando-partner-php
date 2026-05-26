# PartnerRevokeAccountResponse


## Fields

| Field                                                                              | Type                                                                               | Required                                                                           | Description                                                                        |
| ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| `status`                                                                           | [Operations\AccountsRevokeStatus](../../Models/Operations/AccountsRevokeStatus.md) | :heavy_check_mark:                                                                 | Link status after revocation                                                       |
| `revokedAt`                                                                        | [\DateTime](https://www.php.net/manual/en/class.datetime.php)                      | :heavy_check_mark:                                                                 | ISO 8601 timestamp when the link was revoked                                       |