## Context

Checkout currently discovers third-party delivery options only after `form.address_id` changes, but renders the shipping-method card before the address card. The complete customer-address form, dependent regional lookups, and address-storage endpoint already live in the Account surface. This change needs the same form capability in checkout without creating a second validation or region-loading contract.

## Goals / Non-Goals

**Goals:**

- Lead checkout with address selection, then reveal delivery choices for that address.
- Let customers add a complete, customer-managed address in a reusable modal without leaving checkout.
- Select the newly created address and use the existing latest-request and courier-reconciliation behavior to refresh delivery choices.
- Preserve the Account page's existing address-management capabilities.

**Non-Goals:**

- Change third-party shipping providers, quotes, checkout payloads, existing address validation rules, or database schema.
- Add editing or deletion of existing addresses to checkout.

## Decisions

### Share the address form as a component

Extract the complete add-address form into a reusable frontend component that owns field state, server error rendering, dependent region loading, submit loading, and focusable modal semantics. The Account page and checkout will pass the same initial province data and use the existing storage and lookup routes. This prevents regional-form behavior from drifting. Duplicating the Account form in checkout was rejected because it would duplicate the most validation-heavy customer form.

### Keep the existing server endpoint and redirect contract

The modal will submit to the existing customer address-store route, preserve its server-side validation, and let the redirect refresh the current Inertia page props. The checkout shell will reconcile the refreshed address list, select the just-created address, close the modal, and rely on the existing `address_id` watcher to request current delivery options. A new API endpoint was rejected because the existing authenticated endpoint already owns authorization and persistence.

### Treat the modal as a checkout continuation

The address card supplies the clear primary “Tambah Alamat” action. The modal opens as a dialog on larger screens and a near-full-height sheet on mobile, keeps the form in logical field order, traps focus, offers an explicit close/cancel action, and blocks duplicate submission while saving. On success the customer returns to the same checkout context; on validation failure the dialog remains open with field-level errors.

### Make delivery unavailable until address context exists

Address is numbered and ordered first. The delivery card is numbered second and presents an explanatory empty state until an address is selected; quote loading and multi-warehouse courier reconciliation remain unchanged. This matches the shipping provider's address-dependent quote model rather than implying an unavailable choice is actionable.

## Risks / Trade-offs

- [Redirected save can refresh checkout props while the dialog is open] → retain the new-address identity before submit and reconcile against refreshed addresses before closing.
- [Long regional forms can exceed mobile viewport] → use a scrollable dialog body with a fixed action area and retain keyboard-visible inputs.
- [Address changes can invalidate selected couriers] → retain the existing latest-request gate and `reconcileShippingMethods` warning behavior.
- [Customer cannot manage addresses in a private-store context] → honor the existing server authorization and show the returned validation/authorization result; do not bypass the existing route.
