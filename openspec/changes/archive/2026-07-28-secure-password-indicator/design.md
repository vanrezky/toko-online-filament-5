## Context

Customer registration already receives `secure_password` from `RegisterController`, while the server validates passwords through `securePassword(8)`. When enabled, that helper requires a minimum of eight characters plus letters, numbers, and symbols. The registration page currently does not consume the page prop.

## Goals / Non-Goals

**Goals:**

- Expose the existing server-enforced requirements before form submission.
- Update the feedback as the customer types without changing registration validation.
- Keep the inactive-setting form visually and behaviorally unchanged.

**Non-Goals:**

- Change password policy, minimum length, login, reset-password, or admin settings behavior.
- Add client-side submission blocking or a password-strength score.

## Decisions

- Derive four requirement states in `Auth/Register.vue`: eight-character minimum, letter, number, and symbol. This exactly mirrors `securePassword(8)` when `secure_password` is enabled. A generic strength meter was rejected because its score could disagree with server validation.
- Use the controller-provided `secure_password` page prop, instead of the shared `settings` object. The prop is scoped to the registration response and is already supplied by the controller.
- Render a compact, accessible checklist under the password field only when the setting is truthy. Each item communicates pending or satisfied state with text and color, while preserving the existing form layout.
- Add semantic Indonesian and English locale keys. Hardcoded template copy was rejected to preserve the storefront's localization convention.

## Risks / Trade-offs

- [The client checklist can drift if the server policy later changes] → Keep the requirements tied to the current helper and preserve server-side validation as authoritative; update both together if the helper changes.
- [A symbol regex can differ from Laravel's interpretation at an edge case] → Treat non-letter and non-number characters as symbols, matching the practical customer-facing policy; server validation remains final.
- [The page prop can be a non-boolean setting value] → Use truthiness only for conditional display, matching the existing setting handling.

## Migration Plan

No migration or rollout step is needed. Existing settings continue to control server validation and the indicator is shown only when the setting is already enabled. Rolling back removes presentation-only code without affecting stored data.

## Open Questions

None.
