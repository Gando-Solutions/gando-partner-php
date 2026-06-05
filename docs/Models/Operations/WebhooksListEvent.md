# WebhooksListEvent

V1 webhook event types. `deposit.status_changed` is the wildcard delivered for every status transition. `deposit.activated` (-> active), `deposit.captured` (-> captured), `deposit.expired` (-> close), `deposit.cancelled` (-> cancelled) are specific events. `rental_operator.linked` fires when a rental operator account is linked to your partner via connect. When a subscription includes both the wildcard and a specific event, the most specific subscribed event wins for that transition (single delivery per endpoint).


## Values

| Name                   | Value                  |
| ---------------------- | ---------------------- |
| `DepositStatusChanged` | deposit.status_changed |
| `DepositActivated`     | deposit.activated      |
| `DepositCaptured`      | deposit.captured       |
| `DepositExpired`       | deposit.expired        |
| `DepositCancelled`     | deposit.cancelled      |
| `RentalOperatorLinked` | rental_operator.linked |