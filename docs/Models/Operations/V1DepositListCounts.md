# V1DepositListCounts

Present only when `includeCounts=true` and `accountId` query is set


## Fields

| Field                                   | Type                                    | Required                                | Description                             |
| --------------------------------------- | --------------------------------------- | --------------------------------------- | --------------------------------------- |
| `total`                                 | *int*                                   | :heavy_check_mark:                      | Total deposits for the filtered account |
| `byStatus`                              | array<string, *int*>                    | :heavy_check_mark:                      | Deposit count keyed by status           |