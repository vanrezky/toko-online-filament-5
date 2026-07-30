## ADDED Requirements

### Requirement: Checkout accepts an explicit active-cart item selection

The checkout SHALL accept an optional `cart_item_ids` selection. When supplied, it MUST be a non-empty distinct set of item IDs belonging to the authenticated customer's active cart, and checkout MUST reject invalid, stale, duplicate, or foreign item IDs without creating a transaction.

#### Scenario: Customer opens checkout for selected cart items

- **WHEN** an authenticated customer supplies IDs for items in their active cart
- **THEN** checkout SHALL render and calculate only those selected items

#### Scenario: Customer supplies an invalid selection

- **WHEN** a request includes an item ID that is absent from the customer's active cart or repeats an ID
- **THEN** checkout SHALL return a validation error and SHALL not create a transaction

### Requirement: Existing checkout entry points retain all-cart behavior

When `cart_item_ids` is omitted, checkout SHALL use every item in the authenticated customer's active cart for page rendering, shipping, voucher validation, payment eligibility, pricing, reservation, and transaction creation.

#### Scenario: Customer checks out from the existing cart action

- **WHEN** an authenticated customer opens checkout without item IDs
- **THEN** checkout SHALL retain the existing all-active-cart behavior

### Requirement: Selected items remain the sole checkout calculation scope

For a valid explicit selection, shipping costs, voucher validation, subtotal, credit-limit and balance eligibility, installment eligibility, final flash-sale pricing, reservations, and transaction products SHALL use exactly the selected active-cart items.

#### Scenario: Customer completes partial checkout

- **WHEN** a selected checkout contains items from one or more warehouses while other active-cart items are unselected
- **THEN** the resulting transaction and all checkout calculations SHALL include only the selected items

### Requirement: Successful partial checkout preserves unselected cart items

After a successful selected checkout, the system SHALL remove only the purchased active-cart items. The active cart SHALL become `checked_out` only when it has no remaining items.

#### Scenario: Cart retains an unselected item

- **WHEN** a transaction succeeds for a strict subset of the active cart
- **THEN** each unselected item SHALL remain active and available in the customer's cart

#### Scenario: Checkout purchases the final active item

- **WHEN** a transaction succeeds and no active-cart item remains
- **THEN** the cart SHALL be marked `checked_out`

### Requirement: Buy Now carries the resolved cart item into checkout

The Buy Now action SHALL add or merge its requested product into the customer's active cart and navigate to checkout with the resolved cart item ID as the explicit selection.

#### Scenario: Buy Now targets an existing matching cart item

- **WHEN** a customer chooses Buy Now for a product and variant already present in the active cart
- **THEN** checkout SHALL select the merged active-cart item rather than every cart item
