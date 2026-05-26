# ClientsListRequest


## Fields

| Field                                                 | Type                                                  | Required                                              | Description                                           |
| ----------------------------------------------------- | ----------------------------------------------------- | ----------------------------------------------------- | ----------------------------------------------------- |
| `accountId`                                           | *?string*                                             | :heavy_minus_sign:                                    | Filter clients to this linked rental operator account |
| `page`                                                | *?int*                                                | :heavy_minus_sign:                                    | Page number (1-based)                                 |
| `limit`                                               | *?int*                                                | :heavy_minus_sign:                                    | Items per page (max 100)                              |
| `search`                                              | *?string*                                             | :heavy_minus_sign:                                    | Case-insensitive search on name, email, or company    |