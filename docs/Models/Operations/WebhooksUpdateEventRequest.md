# WebhooksUpdateEventRequest

Partner webhook event types. `rental_operator.linked` fires when a rental operator account is linked to your partner via connect. The five deposit events mirror rental-operator webhooks: `deposit.status_changed` is the wildcard delivered for every status transition; `deposit.activated` (-> active), `deposit.captured` (-> captured), `deposit.expired` (-> close, natural end of contract), `deposit.cancelled` (-> cancelled, manual cancellation) are the specific events. When an endpoint subscribes to both the wildcard and a specific event, the most specific subscribed event wins for that transition (single delivery per endpoint).


## Values

| Name                   | Value                  |
| ---------------------- | ---------------------- |
| `RentalOperatorLinked` | rental_operator.linked |
| `DepositStatusChanged` | deposit.status_changed |
| `DepositActivated`     | deposit.activated      |
| `DepositCaptured`      | deposit.captured       |
| `DepositExpired`       | deposit.expired        |
| `DepositCancelled`     | deposit.cancelled      |