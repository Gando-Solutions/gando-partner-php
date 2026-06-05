# DepositsCaptureResponseBody

Capture processed (check `status` for outcome)


## Fields

| Field                                                                    | Type                                                                     | Required                                                                 | Description                                                              | Example                                                                  |
| ------------------------------------------------------------------------ | ------------------------------------------------------------------------ | ------------------------------------------------------------------------ | ------------------------------------------------------------------------ | ------------------------------------------------------------------------ |
| `success`                                                                | *bool*                                                                   | :heavy_check_mark:                                                       | Always `true` for successful responses                                   |                                                                          |
| `data`                                                                   | [Operations\V1CaptureResult](../../Models/Operations/V1CaptureResult.md) | :heavy_check_mark:                                                       | N/A                                                                      | {<br/>"capturedAmount": 50000,<br/>"status": "succeeded"<br/>}           |
| `message`                                                                | *?string*                                                                | :heavy_minus_sign:                                                       | Optional human-readable message                                          |                                                                          |