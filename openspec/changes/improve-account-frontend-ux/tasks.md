## 1. Account wallet destination

- [x] 1.1 Add a localized Wallet Balance item to the shared desktop and mobile account navigation, visible only when balance is enabled.
- [x] 1.2 Move the balance history block from the overview into the URL-backed `balance` account section and show the current balance with the existing history data.
- [x] 1.3 Add focused frontend assertions for wallet navigation, active URL state, overview history removal, and balance-disabled behavior.

## 2. Registration and localization

- [x] 2.1 Render the Register legal links with new-tab and safe opener attributes while preserving the existing generated routes and consent form behavior.
- [x] 2.2 Update the balance description and wallet navigation/history labels in both Indonesian and English locale files.
- [x] 2.3 Add focused frontend assertions for legal-link attributes and localized wallet copy.

## 3. Empty-state consistency

- [x] 3.1 Apply the Wishlist empty-state box, spacing, border, icon, title, and description treatment to empty shipping addresses.
- [x] 3.2 Apply the same Wishlist-based treatment to empty orders while retaining order-specific text and icon behavior.
- [x] 3.3 Add or update frontend assertions for populated-versus-empty rendering and the shared empty-state class contract.

## 4. Validation and traceability

- [x] 4.1 Run focused Vitest coverage for the affected account, order, wishlist, and new regression tests.
- [x] 4.2 Run the full frontend test suite and production build.
- [x] 4.3 Run strict OpenSpec validation and review the final diff against Issue #92 acceptance criteria.
