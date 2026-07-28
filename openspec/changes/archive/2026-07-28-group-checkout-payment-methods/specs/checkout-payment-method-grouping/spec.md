## ADDED Requirements

### Requirement: Checkout explains active payment choices in customer language
The checkout SHALL ask the customer to choose how they want to pay and MUST describe each active option by its funding source and commitment, while retaining its existing payment behavior. The balance option MUST communicate the available balance and whether it is sufficient. The full-payment option MUST explain that the order total is deducted from the credit limit. The installment option MUST explain that the customer will choose a term and pay monthly from the credit limit.

#### Scenario: Customer views available payment methods
- **WHEN** a customer opens checkout
- **THEN** each active payment option explains the source of funds and its customer-facing payment commitment before the customer selects it

#### Scenario: Customer cannot use installment for the current order
- **WHEN** the order does not meet the installment minimum
- **THEN** the installment option remains unavailable and explains the minimum order amount in the option itself

#### Scenario: Customer selects installment
- **WHEN** a customer selects the installment option
- **THEN** the tenor selection appears directly after the active payment choices and before the future-methods section

#### Scenario: Customer views tenor choices on a narrow screen
- **WHEN** a customer views the installment tenor choices on a narrow screen
- **THEN** tenor choices remain in a compact two-column grid and each monthly payment fits within its own card without overlapping another card

#### Scenario: Customer views tenor choices on a wide screen
- **WHEN** a customer views the installment tenor choices on a wide screen
- **THEN** the tenor grid uses the available space compactly with spacing consistent with the active payment choices

### Requirement: Checkout shows credit limit before the payment decision
The checkout SHALL show a customer's remaining credit limit as compact context before the customer chooses a credit-limit payment option. Any over-limit warning MUST remain adjacent to the active payment decision.

#### Scenario: Customer views payment choices with credit limit enabled
- **WHEN** a customer opens checkout and credit-limit enforcement is enabled
- **THEN** the remaining credit limit is visible before the active payment choices

### Requirement: Checkout presents gateway options as unavailable
The checkout SHALL display BCA, BRI, Credit Card, and ShopeePay in a separate future-methods section. Each gateway option MUST be disabled and visibly state that it is not yet available.

#### Scenario: Customer views payment gateways
- **WHEN** a customer opens checkout
- **THEN** BCA, BRI, Credit Card, and ShopeePay appear as disabled gateway options with a not-yet-available notice

#### Scenario: Customer interacts with an unavailable gateway
- **WHEN** a customer clicks or focuses an unavailable gateway option
- **THEN** the selected active payment method and checkout payment payload remain unchanged
