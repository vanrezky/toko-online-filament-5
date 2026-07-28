## Why

Issue #19: changing the delivery address reloads shipping options and always replaces the customer's selected courier with the first available option. This changes a checkout choice without user intent and can produce an unexpected delivery cost.

## What Changes

- Preserve a selected courier for each warehouse when it remains available after shipping costs are reloaded.
- Refresh the preserved selection with the latest price, estimation, and weight from the shipping-cost response.
- Use an available fallback only when the previous courier is no longer offered, and notify the customer.
- Ensure a stale shipping-cost response cannot overwrite the result for the most recently selected address.

## Capabilities

### New Capabilities

- `checkout-shipping-selection`: Preserve valid customer courier choices while shipping options are refreshed during checkout.

### Modified Capabilities

- None.

## Impact

- `resources/js/frontend/pages/Checkout/Index.vue` shipping-cost refresh and checkout form state.
- Frontend checkout tests covering retained, unavailable, multi-warehouse, and stale-response selections.
- No changes to the shipping-cost API contract, Apicoid integration, or payment flow.
