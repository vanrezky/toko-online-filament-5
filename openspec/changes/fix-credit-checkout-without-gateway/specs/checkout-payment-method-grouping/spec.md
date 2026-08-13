## MODIFIED Requirements

### Requirement: Checkout presents gateway options as unavailable
The checkout SHALL display BCA, BRI, Credit Card, and ShopeePay in a separate future-methods section. Each gateway option MUST be disabled and visibly state that it is not yet available. The availability or configuration of those external gateways MUST NOT change the checkout contract for the active full credit-limit or installment payment types.

#### Scenario: Customer views payment gateways
- **WHEN** a customer opens checkout
- **THEN** BCA, BRI, Credit Card, and ShopeePay appear as disabled gateway options with a not-yet-available notice

#### Scenario: Customer interacts with an unavailable gateway
- **WHEN** a customer clicks or focuses an unavailable gateway option
- **THEN** the selected active payment method and checkout payment payload remain unchanged

#### Scenario: No gateway is active
- **WHEN** no external payment gateway is active
- **THEN** full credit-limit and valid installment checkout remain available as their existing internal payment methods
