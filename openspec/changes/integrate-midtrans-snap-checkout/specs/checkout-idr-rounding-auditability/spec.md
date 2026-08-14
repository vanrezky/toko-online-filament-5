## ADDED Requirements

### Requirement: Checkout persists whole-IDR amounts as the financial source of truth
The system SHALL apply half-up whole-IDR rounding during the final server-side checkout recalculation before creating a transaction. Persisted transaction product prices, discounts, line subtotals, shipping amounts, voucher discounts, billing/credit calculations, and transaction total MUST use the rounded values. The persisted total MUST equal the sum of persisted order components and the amount sent to an external payment gateway.

#### Scenario: Checkout contains decimal product pricing
- **WHEN** the final checkout calculation contains fractional product price or discount values
- **THEN** the created transaction stores whole-IDR product and line amounts and its total equals the resulting persisted order components

#### Scenario: Checkout uses Midtrans
- **WHEN** a customer submits an order through Midtrans with fractional source pricing
- **THEN** the persisted transaction total and every Snap payload amount use the same whole-IDR amount

### Requirement: Checkout and order detail show payable persisted amounts
The checkout summary SHALL preview whole-IDR payable amounts using the same component rounding as final checkout. Order detail SHALL display the persisted rounded product, discount, shipping, voucher, and total amounts without recomputing from pre-rounding source values.

#### Scenario: Customer reviews an order with rounded components
- **WHEN** a customer opens checkout or the resulting order detail
- **THEN** each displayed financial component and the final payable total are whole-IDR values consistent with the saved transaction
