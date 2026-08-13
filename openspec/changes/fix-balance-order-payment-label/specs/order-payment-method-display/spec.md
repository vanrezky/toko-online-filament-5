## ADDED Requirements

### Requirement: Accurate order payment-method display
Customer order detail and Filament transaction detail SHALL derive their payment-method labels from the persisted transaction payment type or payment method.

#### Scenario: Store balance transaction
- **WHEN** an order has `payment_type=balance` or `payment_method=saldo`
- **THEN** the order detail displays the localized store-balance payment label

#### Scenario: Admin transaction detail for store balance
- **WHEN** an administrator views or edits a transaction with `payment_type=balance`
- **THEN** the transaction detail displays the store-balance payment type without allowing payment data to be changed

#### Scenario: Credit-limit transactions
- **WHEN** an order is paid in full from credit limit or by an installment plan
- **THEN** the order detail retains its distinct full-credit or installment payment label

#### Scenario: Unknown payment representation
- **WHEN** an order has no recognized balance or installment payment representation
- **THEN** the order detail uses the existing full-credit payment label as its fallback
