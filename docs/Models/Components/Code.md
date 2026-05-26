# Code

Machine-readable error code — exhaustive enum in OpenAPI (`PartnerErrorCode`).


## Values

| Name                           | Value                          |
| ------------------------------ | ------------------------------ |
| `InvalidRequest`               | invalid_request                |
| `InvalidJsonBody`              | invalid_json_body              |
| `MissingApiKey`                | missing_api_key                |
| `InvalidApiKey`                | invalid_api_key                |
| `ApiKeyRevoked`                | api_key_revoked                |
| `AccountNotLinked`             | account_not_linked             |
| `AccountRevoked`               | account_revoked                |
| `DepositNotFound`              | deposit_not_found              |
| `DepositAccessDenied`          | deposit_access_denied          |
| `ClientNotFound`               | client_not_found               |
| `ClientAccessDenied`           | client_access_denied           |
| `WebhookNotFound`              | webhook_not_found              |
| `WebhookAccessDenied`          | webhook_access_denied          |
| `CaptureNotFound`              | capture_not_found              |
| `DepositAlreadyCaptured`       | deposit_already_captured       |
| `DepositInvalidStatus`         | deposit_invalid_status         |
| `DepositMissingPaymentMethod`  | deposit_missing_payment_method |
| `DepositMissingPaymentIntent`  | deposit_missing_payment_intent |
| `InvalidCaptureAmount`         | invalid_capture_amount         |
| `CaptureAmountTooLow`          | capture_amount_too_low         |
| `CaptureAmountExceedsDeposit`  | capture_amount_exceeds_deposit |
| `AccountNotCaptureReady`       | account_not_capture_ready      |
| `PaymentProcessorUnavailable`  | payment_processor_unavailable  |
| `PaymentProcessorError`        | payment_processor_error        |
| `InvalidClientId`              | invalid_client_id              |
| `ClientNotLinkedToDeposit`     | client_not_linked_to_deposit   |
| `ScoringNotFound`              | scoring_not_found              |
| `InvalidIdempotencyKey`        | invalid_idempotency_key        |
| `IdempotencyKeyConflict`       | idempotency_key_conflict       |
| `RedisUnavailable`             | redis_unavailable              |
| `RateLimited`                  | rate_limited                   |
| `InternalError`                | internal_error                 |
| `BadGateway`                   | bad_gateway                    |
| `InvalidParams`                | invalid_params                 |
| `PartnerNotFound`              | partner_not_found              |
| `MissingConnectSecret`         | missing_connect_secret         |
| `InvalidSignature`             | invalid_signature              |
| `Expired`                      | expired                        |