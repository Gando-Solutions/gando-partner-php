# DepositsGetPaymentMethodResponseBody

Masked PAN / brand


## Fields

| Field                                                                            | Type                                                                             | Required                                                                         | Description                                                                      | Example                                                                          |
| -------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| `success`                                                                        | *bool*                                                                           | :heavy_check_mark:                                                               | Always `true` for successful responses                                           |                                                                                  |
| `data`                                                                           | [Operations\V1PaymentMethodInfo](../../Models/Operations/V1PaymentMethodInfo.md) | :heavy_check_mark:                                                               | N/A                                                                              | {<br/>"maskedPan": "•••• 4242",<br/>"country": "FR",<br/>"brand": "visa"<br/>}   |
| `message`                                                                        | *?string*                                                                        | :heavy_minus_sign:                                                               | Optional human-readable message                                                  |                                                                                  |