## ADDED Requirements

### Requirement: Checkout can initiate a configured Midtrans Snap payment
The system SHALL present Midtrans as a selectable external payment method only when Midtrans is the active configured gateway. When a customer selects Midtrans, checkout MUST create the local transaction using reconciled server-side cart, voucher, shipping, and reservation data, then request a Snap token on the backend. The response MUST expose only Snap token, Client Key, provider mode, and payment URL metadata required by the browser and MUST NOT expose the Server Key.

#### Scenario: Customer starts a configured Midtrans payment
- **WHEN** an authenticated customer submits a valid checkout with Midtrans selected while Midtrans is active and configured
- **THEN** the system creates one pending local transaction and returns browser-safe Snap metadata for that transaction

#### Scenario: Midtrans is unavailable or unconfigured
- **WHEN** a customer submits Midtrans payment while it is not the active configured gateway
- **THEN** the system rejects the selection without creating a transaction or exposing gateway credentials

### Requirement: Snap payload equals the local transaction total
The system SHALL create a Midtrans Snap request with a unique merchant order ID, IDR integer gross amount, and item details derived from the persisted transaction. The total of item details MUST equal the local transaction total and Snap gross amount exactly. Product, voucher discount, shipping, and fee entries MUST be represented without negative line values or duplicate Midtrans item IDs.

#### Scenario: Checkout includes products, voucher, and shipping
- **WHEN** the persisted Midtrans transaction includes discounted products and a shipping charge
- **THEN** the generated Snap payload has an exact integer item-detail total equal to the local transaction total

#### Scenario: Payload totals cannot be reconciled
- **WHEN** the gateway cannot build an exact Snap item total from the persisted transaction
- **THEN** it does not call Midtrans and returns a payment-initiation error without exposing credentials

### Requirement: Customer can resume an unpaid Midtrans payment safely
The system SHALL allow only the owning customer to request payment for an eligible pending Midtrans order. A resume action MUST use the existing local transaction and MUST NOT create another local transaction, duplicate cart mutation, voucher usage, or flash-sale reservation.

#### Scenario: Customer resumes an eligible pending order
- **WHEN** the owning customer requests payment for a pending Midtrans order
- **THEN** the system returns payment metadata for the existing order without creating another local order or checkout side effect

#### Scenario: Customer resumes or changes the Midtrans channel from order detail
- **WHEN** the owning customer views a packed full Midtrans order with pending billing
- **THEN** order detail presents an action that opens Snap to resume payment or select another available Midtrans channel without changing the local payment type or billing contract

#### Scenario: Customer attempts to pay another customer's order
- **WHEN** a customer requests payment for an order they do not own
- **THEN** the system denies the request without requesting a Snap token

### Requirement: Browser callbacks do not determine payment state
The browser SHALL load Snap JavaScript with the configured Client Key and mode, use a backend-issued token, and redirect the customer to the order after each Snap callback. Browser callback payloads MUST NOT update local billing or transaction status.

#### Scenario: Snap payment completes or is closed
- **WHEN** Snap invokes success, pending, error, or close callback
- **THEN** the browser navigates to the order detail and local payment state remains controlled by server-side reconciliation
