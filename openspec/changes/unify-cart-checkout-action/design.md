## Context

The Cart page currently renders a selected-item checkout button and a separate all-items Checkout link. The existing selected-item action already passes `cart_item_ids` to the named Checkout route, while Checkout retains responsibility for validating the submitted IDs.

## Goals / Non-Goals

**Goals:**

- Provide one visually primary Cart checkout action.
- Preserve the selected-item route payload for both partial and full selections.
- Prevent checkout navigation when the selection is empty.
- Cover the user-facing path with Playwright regression coverage.

**Non-Goals:**

- Change Checkout request validation, ownership checks, pricing, payment, shipping, or Cart persistence.
- Add a new API or alter the meaning of `cart_item_ids`.

## Decisions

- Keep `checkoutSelected` as the sole Cart navigation path and remove the separate all-items link. It already communicates the active selection to Checkout, so it preserves the backend contract for both selection sizes.
- Keep the existing disabled state when `selectedItems.length === 0`; this prevents an empty client request without replacing server-side validation.
- Use the existing primary Button styling and label for the single action. This preserves the Cart's visual hierarchy and avoids a competing secondary checkout affordance.
- Add an E2E scenario that logs in, adds two distinct catalog products, selects all Cart items, and uses the sole checkout action. This exercises the workflow without duplicating backend validation tests.

## Risks / Trade-offs

- [Customer expects a separate checkout-all action] → The select-all checkbox continues to make all items the active selection before the same checkout button is used.
- [Client-side selection is manipulated] → Existing Checkout validation remains the authority; this change only preserves the existing payload route.
