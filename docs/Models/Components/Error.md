# Error


## Fields

| Field                                                                          | Type                                                                           | Required                                                                       | Description                                                                    |
| ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------ |
| `code`                                                                         | [Components\Code](../../Models/Components/Code.md)                             | :heavy_check_mark:                                                             | Machine-readable error code — exhaustive enum in OpenAPI (`PartnerErrorCode`). |
| `message`                                                                      | *string*                                                                       | :heavy_check_mark:                                                             | Human-readable error message.                                                  |
| `requestId`                                                                    | *string*                                                                       | :heavy_check_mark:                                                             | Request correlation ID — include in support tickets.                           |
| `details`                                                                      | array<string, *mixed*>                                                         | :heavy_minus_sign:                                                             | Optional structured details.                                                   |