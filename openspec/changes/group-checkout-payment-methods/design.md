## Context

Issue #14 (`https://github.com/vanrezky/toko-online-filament3/issues/14`) is limited to the checkout payment-method presentation. `Checkout/Index.vue` already owns selected payment state and its checkout submission payload; the change must not alter either.

## Goals / Non-Goals

**Goals:**

- Make active and unavailable payment options visually distinct on desktop and mobile.
- Keep existing active option selection and validation unchanged.

**Non-Goals:**

- Gateway activation, payment API changes, or transaction-payload changes.
- New payment provider configuration.

## Decisions

- Render active methods in their existing selectable controls under an available-method heading. This preserves current selection handlers and validation rather than introducing a second payment model.
- Render gateway options from static presentation data as disabled controls with a localized maintenance label. Disabled controls cannot invoke selection or alter the payload.
- Add semantic locale keys in Indonesian and English; do not hardcode new customer-facing text.

## Risks / Trade-offs

- [Disabled controls could look selectable] → use muted styling, a disabled cursor, and an explicit maintenance badge.
- [Visual refactor could affect payment state] → retain the existing active-method bindings and verify the checkout build.
