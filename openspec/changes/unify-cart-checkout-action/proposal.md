## Why

The Cart currently presents separate checkout actions for selected items and all items. The all-items action can bypass the customer's current selection, making the checkout path unclear.

## What Changes

- Replace the two Cart checkout actions with one primary checkout button.
- Route the current selected cart item IDs to Checkout whether the selection contains some or all cart items.
- Keep the button unavailable until at least one item is selected.

## Capabilities

### New Capabilities

- `cart-selected-checkout-action`: Provide one Cart checkout action that consistently uses the customer's active item selection.

### Modified Capabilities

- None.

## Impact

- Affected frontend: `resources/js/frontend/Pages/Cart/Index.vue` and its regression coverage.
- No backend API, pricing, payment, shipping, or Checkout validation changes.
