## ADDED Requirements

### Requirement: Internal credit checkout is independent of gateway configuration
The system SHALL complete a valid full credit-limit or installment checkout without creating an external gateway payment, regardless of whether an external gateway is active or configured. It MUST retain the existing credit-limit validation, transaction billing state, due-date calculation, installment creation, voucher handling, shipping details, flash-sale reservation, selected-cart completion, and payment-request notification behavior.

#### Scenario: Full credit checkout with no active gateway
- **WHEN** a customer with sufficient available credit submits a valid full credit-limit checkout while no external payment gateway is active
- **THEN** checkout succeeds and creates the existing pending billing transaction without calling an external gateway

#### Scenario: Installment checkout with no active gateway
- **WHEN** a customer selects a valid installment plan, has sufficient available credit for its total, and submits a valid checkout while no external payment gateway is active
- **THEN** checkout succeeds and creates the transaction and installment records without calling an external gateway

### Requirement: Balance settlement remains distinct from credit-limit checkout
The system SHALL continue to settle balance checkout through the internal balance ledger and MUST NOT call an external gateway for balance checkout.

#### Scenario: Balance checkout bypasses external gateway
- **WHEN** a customer with sufficient enabled balance submits a valid balance checkout
- **THEN** checkout debits the balance ledger, marks the transaction paid, and does not create an external gateway payment
