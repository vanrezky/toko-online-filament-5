## Why

Issue #6 reports four checkout presentation and interaction gaps: courier names are truncated, delivery estimates are too small, courier logos are missing, and customer-managed addresses cannot be edited from checkout.

## What Changes

- Keep courier names readable in shipping-method cards without ellipsis.
- Increase delivery-estimate readability while preserving responsive card layout.
- Resolve known courier logos through the existing frontend shipping module and keep the current icon for unknown or unavailable logos.
- Add an accessible edit action for customer-managed checkout addresses.
- Reuse the existing address form and refresh shipping costs when the selected address is edited.
- Preserve the existing address-first checkout flow, courier selection reconciliation, pickup behavior, and checkout payload.

## Capabilities

### Modified Capabilities

- `checkout-address-modal`: Extend the existing in-context address form from add-only to safe editing of customer-managed addresses.
- `checkout-shipping-selection`: Improve courier card information hierarchy and provide logo fallback behavior without changing quote or selection contracts.

## Impact

- `resources/js/frontend/Pages/Checkout/Index.vue`
- `resources/js/frontend/lib/shippingMethods.js`
- `tests/Frontend/CheckoutShippingMethods.test.js`
- Existing public courier assets and address routes are reused.
- No database schema, endpoint contract, provider, or checkout payload change.
