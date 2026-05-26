# WebhooksUpdateEventResponse

Partner webhook event types. `rental_operator.linked` fires when a rental operator account is linked to your partner via connect. The five caution events mirror rental-operator webhooks: `caution.status_changed` is the wildcard delivered for every status transition; `caution.activated` (-> active), `caution.captured` (-> captured), `caution.expired` (-> close, natural end of contract), `caution.cancelled` (-> cancelled, manual cancellation) are the specific events. When an endpoint subscribes to both the wildcard and a specific event, the most specific subscribed event wins for that transition (single delivery per endpoint).


## Values

| Name                   | Value                  |
| ---------------------- | ---------------------- |
| `RentalOperatorLinked` | rental_operator.linked |
| `CautionStatusChanged` | caution.status_changed |
| `CautionActivated`     | caution.activated      |
| `CautionCaptured`      | caution.captured       |
| `CautionExpired`       | caution.expired        |
| `CautionCancelled`     | caution.cancelled      |