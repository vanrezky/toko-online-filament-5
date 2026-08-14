## Why

Issue #32 addresses a checkout sequence that asks customers to choose delivery before the third-party shipping service can price it. Customers must also leave checkout to add an address, interrupting the flow before shipping options can be calculated.

## What Changes

- Make shipping address step 1 and shipping method step 2 in checkout.
- Add a reusable customer-address form modal to checkout, using the existing address validation and regional lookup endpoints.
- Select a newly saved address immediately and refresh third-party shipping options through the existing reconciliation flow.
- Preserve the account address-management experience while sharing the address-form contract instead of duplicating it.

## Capabilities

### New Capabilities

- `checkout-address-modal`: Let a customer create a complete shipping address inside checkout and continue with it selected.

### Modified Capabilities

- `checkout-shipping-selection`: Make address selection precede shipping-option discovery while preserving courier reconciliation for address-driven refreshes.

## Impact

- `resources/js/frontend/Pages/Checkout/Index.vue`, account address UI, shared frontend components, and Indonesian/English frontend translations.
- Existing account address storage and regional lookup routes; no new schema, endpoint contract, provider, or checkout payload change.
