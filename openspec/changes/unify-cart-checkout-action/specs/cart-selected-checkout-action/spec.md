## ADDED Requirements

### Requirement: Unified Cart checkout action

The Cart page SHALL render exactly one primary checkout action for a non-empty Cart. The action MUST use the Cart's currently selected item IDs when navigating to Checkout.

#### Scenario: Checkout a subset of Cart items

- **WHEN** a customer selects one or more, but not all, Cart items and activates the checkout action
- **THEN** the application SHALL navigate to Checkout with only the selected `cart_item_ids`

#### Scenario: Checkout all Cart items

- **WHEN** a customer selects all Cart items and activates the checkout action
- **THEN** the application SHALL navigate to Checkout with every Cart item ID selected

#### Scenario: No items are selected

- **WHEN** a customer clears the Cart item selection
- **THEN** the checkout action SHALL be disabled and SHALL not navigate to Checkout

### Requirement: Existing Checkout validation remains authoritative

The Cart checkout action SHALL continue to use the existing Checkout route and SHALL NOT modify backend validation for submitted Cart item IDs.

#### Scenario: Selected Cart IDs are submitted

- **WHEN** a customer activates the enabled Cart checkout action
- **THEN** the existing Checkout flow SHALL receive the selected `cart_item_ids` for its current validation
