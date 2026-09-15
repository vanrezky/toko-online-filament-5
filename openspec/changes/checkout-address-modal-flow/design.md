## Context

Checkout already uses an address-first flow, a shared `AddressForm`, existing customer address routes, and latest-request shipping reconciliation. The requested changes are limited to the checkout presentation and address-edit affordance.

## Goals / Non-Goals

**Goals:**

- Show complete courier names and readable delivery estimates in responsive shipping cards.
- Resolve known courier logos from existing public assets in the shared frontend shipping module.
- Keep pickup and unknown couriers on the current icon fallback.
- Let customers edit only addresses they are authorized to manage, using the existing form and update route.
- Refresh shipping quotes when the selected address is edited while preserving the checkout selection contract.

**Non-Goals:**

- Change third-party shipping providers, quotes, payment behavior, checkout payloads, existing validation rules, or database schema.
- Add new courier image assets.
- Expose editing for admin-managed addresses.

## Decisions

### Reuse the existing shipping module for logo mapping

Add a small courier-code-to-existing-asset mapping beside `reconcileShippingMethods` in `resources/js/frontend/lib/shippingMethods.js`. The resolver returns `null` for unknown codes, allowing the checkout card to render the existing `Store` or `Truck` icon. A new service or backend response field is unnecessary because the assets are already public and the requirement is presentation-only.

### Keep card content flexible

Remove the courier-name truncation and use wrapping-friendly text classes. Increase estimation text to the normal small UI size while keeping price and controls shrink-safe so long names do not overflow neighboring content.

### Reuse `AddressForm` for editing

Track the address currently being edited in checkout and pass it to the existing shared form. Render the edit button outside the radio element's interactive region, show it only when `can_customer_manage` is true, and stop its click from changing the selected address.

### Refresh only when the edited address affects shipping

After a successful edit, keep the current address selection. If the edited address is selected, explicitly fetch shipping costs because its id did not change; if another address was edited, preserve the current quote and selection.

## Risks / Trade-offs

- Long courier names increase card height → allow natural wrapping and keep the grid responsive.
- An asset can be missing or renamed → only map verified existing assets and return the current icon fallback for unmapped codes.
- Editing a selected address can change its destination → explicitly refresh its shipping quote after the address list reloads.
- A customer could attempt to edit an admin-managed address → hide the action and retain the server-side authorization guard.
