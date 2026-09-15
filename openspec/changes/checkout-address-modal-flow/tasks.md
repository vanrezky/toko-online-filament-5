## 1. Courier card presentation

- [x] 1.1 Add a shared courier-logo resolver using only the existing verified public courier assets and return no logo for unknown codes.
- [x] 1.2 Update checkout shipping cards to show full courier names, readable estimations, known logos, and the current pickup/truck fallback icons without changing selection payloads.

## 2. Checkout address editing

- [x] 2.1 Add an accessible edit action only for customer-manageable checkout addresses without making the radio selection control contain another interactive control.
- [x] 2.2 Reuse `AddressForm` in edit mode with localized heading/submit state and preserve existing add-address behavior.
- [x] 2.3 After an edit succeeds, refresh the address list and shipping costs only when the edited address is selected; preserve the current selected address otherwise.

## 3. Verification

- [x] 3.1 Add focused frontend tests for courier-logo mapping and fallback behavior.
- [x] 3.2 Run focused frontend tests and browser QA for full names, estimation readability, logos/fallback, address edit, and selected-address quote refresh at desktop and mobile widths.
- [x] 3.3 Run `npm run build`, strict OpenSpec validation, Impeccable detector, and scoped diff checks.
