## MODIFIED Requirements

### Requirement: Checkout explains active payment choices in customer language
The checkout SHALL ask the customer to choose how to pay and MUST describe each active option by its funding source and commitment while retaining its existing payment behavior. The balance option MUST communicate the available balance and whether it is sufficient. The full-payment option MUST explain that the order total is deducted from the credit limit. The installment option MUST explain that the customer will choose a term and pay monthly from the credit limit. Checkout SHALL submit the browser's valid IANA timezone so the server can create an auditable transaction deadline for every payment method and gateway.

#### Scenario: Customer views available payment methods
- **WHEN** a customer opens checkout
- **THEN** each active payment option explains the source of funds and its customer-facing payment commitment before the customer selects it

#### Scenario: Customer cannot use installment for the current order
- **WHEN** the order does not meet the installment minimum
- **THEN** the installment option remains unavailable and explains the minimum order amount in the option itself

#### Scenario: Customer selects a payment method
- **WHEN** a customer selects an available payment method and submits checkout from a browser with a valid IANA timezone
- **THEN** the checkout payload includes that timezone while retaining the existing payment contracts
