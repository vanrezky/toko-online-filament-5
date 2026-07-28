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
- **THEN** each tenor card has enough horizontal space for its monthly payment without overlapping another card

### Requirement: Checkout presents gateway options as unavailable
The checkout SHALL display BCA, BRI, Credit Card, and ShopeePay in a separate future-methods section. Each gateway option MUST be disabled and visibly state that it is not yet available.

#### Scenario: Customer views payment gateways
- **WHEN** a customer opens checkout
- **THEN** BCA, BRI, Credit Card, and ShopeePay appear as disabled gateway options with a not-yet-available notice

#### Scenario: Customer interacts with an unavailable gateway
- **WHEN** a customer clicks or focuses an unavailable gateway option
- **THEN** the selected active payment method and checkout payment payload remain unchanged
