# PartnerCaptureInfo


## Fields

| Field                                                   | Type                                                    | Required                                                | Description                                             |
| ------------------------------------------------------- | ------------------------------------------------------- | ------------------------------------------------------- | ------------------------------------------------------- |
| `amountCents`                                           | *int*                                                   | :heavy_check_mark:                                      | Capture amount in EUR cents                             |
| `feeCents`                                              | *int*                                                   | :heavy_check_mark:                                      | Gando collection fee in EUR cents                       |
| `netCents`                                              | *int*                                                   | :heavy_check_mark:                                      | Net amount credited to the rental operator in EUR cents |
| `reason`                                                | *string*                                                | :heavy_check_mark:                                      | Capture reason if provided                              |
| `status`                                                | *string*                                                | :heavy_check_mark:                                      | Capture status (e.g. paid, pending, failed)             |