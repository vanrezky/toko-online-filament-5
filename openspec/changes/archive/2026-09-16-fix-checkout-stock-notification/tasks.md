## 1. Checkout Error Mapping

- [x] 1.1 Convert missing active-cart state during `POST /checkout` order submission into a dedicated controlled stock-unavailable error.
- [x] 1.2 Map the error to a localized client-safe response and show it with the existing `vue-sonner` error toast without changing unrelated cart or shipping-cost lookup behavior.

## 2. Regression Coverage

- [x] 2.1 Add a backend feature test that completes the first checkout, submits the second customer's stale checkout request, and asserts the stock message, conflict response, and absence of a second transaction.
- [x] 2.2 Assert the successful checkout still reserves the final unit and inventory remains consistent.

## 3. Verification

- [x] 3.1 Run the focused checkout test and frontend checks if the response contract changes.
- [x] 3.2 Run strict OpenSpec validation and mark the issue-linked implementation artifacts complete.
