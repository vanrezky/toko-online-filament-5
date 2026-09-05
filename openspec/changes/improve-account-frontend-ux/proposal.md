## Why

Issue #92 groups several small frontend improvements that make registration and the customer account easier to use. Legal content should not interrupt registration, wallet information should have a clear destination, and empty collections should share one visual pattern.

## What Changes

- Open the Register page Terms & Conditions and Privacy Policy links in a new browser tab.
- Replace prepaid balance wording with wallet balance wording in both supported locales.
- Add a Wallet Balance destination to the existing account navigation.
- Move balance history out of the account overview and show the current balance plus history in the Wallet Balance destination.
- Reuse the Wishlist empty-state box and typography treatment for empty shipping addresses and empty orders.
- Add focused frontend regression coverage for navigation, legal links, wallet placement, and empty-state classes.

## Capabilities

### New Capabilities

- `customer-wallet-balance`: A dedicated URL-backed account destination for the authenticated customer's current wallet balance and balance history.
- `customer-registration-legal-links`: Registration legal links open in a separate browser tab while preserving the existing routes and consent behavior.
- `customer-empty-state-consistency`: Shipping-addresses, orders, and wishlist empty states use a shared visual treatment without changing their data conditions or actions.

### Modified Capabilities

- `customer-account-navigation`: Extend the existing account destination model with the wallet balance destination.

## Impact

- Vue pages and shared account navigation under `resources/js/frontend/`.
- Indonesian and English frontend locale files.
- Existing `AccountController` data props remain the source for the customer balance and ledger; no API, schema, or payment-rule changes are expected.
- Frontend Vitest coverage and production build validation.
