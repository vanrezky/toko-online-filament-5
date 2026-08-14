## MODIFIED Requirements

### Requirement: Checkout presents gateway options according to availability
The checkout SHALL display a Midtrans payment option in a separate external-payment section only when Midtrans is the active configured gateway. The option MUST clearly identify Midtrans as an external payment flow and MUST be selectable without changing the internal full-payment, installment, or balance contracts. BCA, BRI, Credit Card, and ShopeePay future gateway options SHALL remain disabled and visibly state that they are not yet available unless a separately approved gateway integration changes their availability.

#### Scenario: Customer views checkout with Midtrans configured
- **WHEN** a customer opens checkout while Midtrans is active and configured
- **THEN** Midtrans appears as an enabled external payment option and the unrelated future gateway options remain disabled with a not-yet-available notice

#### Scenario: Customer views checkout without configured Midtrans
- **WHEN** a customer opens checkout while Midtrans is inactive or incomplete
- **THEN** no selectable Midtrans option is shown and the selected internal payment method and checkout payload remain unchanged

#### Scenario: Customer interacts with an unavailable gateway
- **WHEN** a customer clicks or focuses an unavailable gateway option
- **THEN** the selected active payment method and checkout payment payload remain unchanged
