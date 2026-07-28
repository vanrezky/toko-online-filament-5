## ADDED Requirements

### Requirement: Checkout separates active and unavailable payment methods
The checkout SHALL group its active payment methods separately from future payment gateways. The active group MUST retain Cicilan, Bayar dengan Saldo when enabled, and Bayar Penuh using their existing behavior.

#### Scenario: Customer views available payment methods
- **WHEN** a customer opens checkout
- **THEN** the active payment methods are presented in a clearly labeled available-methods section

### Requirement: Checkout presents gateway options as unavailable
The checkout SHALL display BCA, BRI, Credit Card, and ShopeePay in a separate Payment Gateway section. Each gateway option MUST be disabled and visibly state that it is under maintenance.

#### Scenario: Customer views payment gateways
- **WHEN** a customer opens checkout
- **THEN** BCA, BRI, Credit Card, and ShopeePay appear as disabled gateway options with a maintenance notice

#### Scenario: Customer interacts with an unavailable gateway
- **WHEN** a customer clicks or focuses an unavailable gateway option
- **THEN** the selected active payment method and checkout payment payload remain unchanged
