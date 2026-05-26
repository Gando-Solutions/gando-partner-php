# PartnerSendDepositMailResponse


## Fields

| Field                                                   | Type                                                    | Required                                                | Description                                             |
| ------------------------------------------------------- | ------------------------------------------------------- | ------------------------------------------------------- | ------------------------------------------------------- |
| `link`                                                  | *string*                                                | :heavy_check_mark:                                      | Deposit completion URL                                  |
| `success`                                               | *bool*                                                  | :heavy_check_mark:                                      | Whether the email was accepted by the provider          |
| `messageId`                                             | *?string*                                               | :heavy_minus_sign:                                      | Transactional email provider message id, when available |