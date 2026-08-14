## 1. Reusable address form

- [ ] 1.1 Extract the add-address fields, dependent regional lookups, server-error rendering, and submit behavior into a reusable frontend form component.
- [ ] 1.2 Add accessible modal and mobile-sheet behavior around the shared form, including close, cancel, loading, and focus handling.
- [ ] 1.3 Refactor account address creation to consume the shared form without changing its existing edit, delete, or default-address behavior.

## 2. Checkout address-first flow

- [ ] 2.1 Supply the existing province data required by the reusable form to checkout without changing address persistence or checkout payload contracts.
- [ ] 2.2 Render shipping address as checkout step 1, add the in-context address action, and preserve selected-address presentation.
- [ ] 2.2a Keep unselected shipping addresses collapsed behind an explicit show-more action.
- [ ] 2.3 Render shipping method as step 2 with an address-first empty state and retain loading, multi-warehouse, and pickup behavior.
- [ ] 2.4 On successful modal save, refresh checkout addresses, select the new address, and preserve latest-request courier reconciliation when shipping options reload.

## 3. Localization and verification

- [ ] 3.1 Add matching Indonesian and English translations for all new checkout and modal UI text.
- [ ] 3.2 Add focused frontend tests for address-first ordering, modal validation/success selection, and shipping-method refresh behavior.
- [ ] 3.3 Run focused frontend tests, `npm run build`, strict OpenSpec validation, Impeccable detector, and scoped diff checks.
