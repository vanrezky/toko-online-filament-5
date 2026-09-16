## Purpose

Provide a predictable checkout response when another customer consumes the last available unit before a stale checkout request is finalized.

## ADDED Requirements

### Requirement: Checkout reports a concurrent stock conflict

When a checkout request can no longer use the cart or cart items because another checkout has consumed the requested last stock, the system SHALL reject the request with a controlled stock-unavailable response.

#### Scenario: Second checkout loses the last-unit race

- **WHEN** two customers submit checkout for the same product with one unit available and the first checkout completes
- **THEN** the second checkout SHALL return a client-safe stock-unavailable error
- **AND** the response SHALL NOT expose a framework model-not-found message

#### Scenario: Stale checkout creates no order

- **WHEN** the second checkout is rejected after the first checkout consumes the requested stock
- **THEN** no transaction SHALL be created for the rejected request
- **AND** product inventory SHALL remain non-negative and consistent

### Requirement: Checkout displays a useful stock message

The checkout response SHALL provide a localized message that tells the customer the requested product stock is exhausted or insufficient.

#### Scenario: Customer receives the stock message

- **WHEN** the checkout request is rejected because the requested stock is no longer available
- **THEN** the frontend SHALL display the localized stock-unavailable message
- **AND** the Indonesian message SHALL be `Produk Sudah Habis.`

### Requirement: Existing successful checkout remains unchanged

A checkout that acquires available stock SHALL continue to complete with its existing payment and order behavior.

#### Scenario: First checkout succeeds

- **WHEN** the first customer submits checkout while the final unit is available
- **THEN** the checkout SHALL create the order and reserve the unit successfully
