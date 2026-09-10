## Purpose

Define one consistent source of inventory truth for products with and without variants across cart, storefront, admin variant management, checkout, and order cancellation.

## ADDED Requirements

### Requirement: Cart quantity uses effective inventory

The cart SHALL validate each line against the stock of its selected product variant, or the product stock when no variant exists. When adding to an existing line, the validation SHALL include the line's current quantity. When a product has variants, a line without a variant SHALL be rejected. Different variants of the same product SHALL be evaluated independently.

#### Scenario: Adding the same variant over available stock is rejected

- **WHEN** a customer adds a quantity that would make the existing product/variant line exceed that variant's stock
- **THEN** the request SHALL be rejected with a validation error
- **AND** the existing cart quantity SHALL remain unchanged

#### Scenario: Updating a cart line against stale stock is rejected

- **WHEN** a customer changes a cart line to a quantity greater than its current effective stock
- **THEN** the request SHALL be rejected with a validation error
- **AND** no cart quantity or price SHALL be persisted from the rejected request

#### Scenario: Variant stock is isolated per variant

- **WHEN** a cart contains two lines for different variants of one product
- **THEN** each line SHALL be validated against its own variant stock
- **AND** the quantity of one variant SHALL NOT consume the other variant's per-line allowance

### Requirement: Product and variant stock stay synchronized

For a product with one or more variants, `products.stock` SHALL equal the sum of its variant stocks after a variant is created, edited, or deleted. A product without variants SHALL retain its directly managed product stock. The product detail response SHALL expose each variant's current stock, and the active variant quantity control SHALL use that stock as its maximum.

#### Scenario: Creating a variant updates aggregate product stock

- **WHEN** an admin creates a product variant
- **THEN** the product stock SHALL equal the sum of all variant stocks

#### Scenario: Editing or deleting a variant updates aggregate product stock

- **WHEN** an admin edits or deletes a product variant
- **THEN** the product stock SHALL be recalculated from the remaining variants
- **AND** no deleted variant stock SHALL remain in the aggregate

#### Scenario: Product detail follows the selected variant

- **WHEN** a customer selects a product variant on the product detail page
- **THEN** the displayed stock SHALL be that variant's current stock
- **AND** the quantity maximum SHALL not exceed that stock

### Requirement: Checkout reserves normal inventory atomically

Checkout SHALL re-read and lock the relevant product and variant rows, validate current stock, and decrement normal product inventory in the same database transaction that creates the order. For products with variants, both the selected variant stock and the aggregate product stock SHALL be updated. The transaction product record SHALL retain the selected variant identity. A stale frontend quantity SHALL be rejected without leaving a transaction, partial stock decrement, cart deletion, or flash-sale reservation behind.

#### Scenario: Checkout decrements product and variant stock

- **WHEN** checkout succeeds for a variant product
- **THEN** the selected variant stock SHALL decrease by the ordered quantity
- **AND** the product stock SHALL decrease by the same quantity
- **AND** the transaction product SHALL reference that variant

#### Scenario: Checkout decrements a non-variant product

- **WHEN** checkout succeeds for a product without variants
- **THEN** the product stock SHALL decrease by the ordered quantity

#### Scenario: Checkout rejects insufficient current stock

- **WHEN** current stock is lower than the cart quantity at the final checkout transaction
- **THEN** checkout SHALL return a validation error
- **AND** no transaction SHALL be created
- **AND** the cart and all inventory rows SHALL remain unchanged

### Requirement: Cancellation restores normal inventory exactly once

Customer cancellation, payment cancellation/expiry webhooks, and automatic expiry SHALL use the same cancellation path. Cancelling an order SHALL restore each transaction product's product stock and, when present, its variant stock within one database transaction. The cancellation path SHALL be serialized on the transaction so repeated cancellation attempts restore stock only once. A successfully processed order SHALL not restore stock.

#### Scenario: Customer cancellation restores reserved stock

- **WHEN** a packed order is cancelled after checkout
- **THEN** each product stock SHALL be incremented by the transaction quantity
- **AND** each referenced variant stock SHALL be incremented by the transaction quantity

#### Scenario: Repeated cancellation is idempotent

- **WHEN** the same order is cancelled more than once through any cancellation entry point
- **THEN** inventory SHALL be restored exactly once
- **AND** the order SHALL remain cancelled

#### Scenario: Expiry and payment cancellation share restoration

- **WHEN** an unpaid order expires or its payment provider reports cancellation/expiry
- **THEN** the shared cancellation path SHALL restore the reserved normal inventory
- **AND** a later duplicate expiry/webhook SHALL not restore it again

#### Scenario: Completed order retains the sale

- **WHEN** an order reaches a successful completed state
- **THEN** its previously decremented product and variant stock SHALL remain decremented
- **AND** no cancellation restoration SHALL occur
