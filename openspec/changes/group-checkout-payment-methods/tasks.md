## 1. Checkout payment presentation

- [x] 1.1 Group existing active payment methods under a localized available-methods section without changing their behavior.
- [x] 1.2 Add disabled BCA, BRI, Credit Card, and ShopeePay gateway options with a localized maintenance notice.
- [x] 1.3 Replace internal payment labels with customer-oriented funding-source and commitment explanations.
- [x] 1.4 Keep the conditional installment tenor flow before the secondary future-methods section.

## 2. Verification

- [x] 2.1 Run the existing frontend test suite; no checkout component-test harness exists to add focused interaction coverage without introducing unrelated test infrastructure.
- [x] 2.2 Run frontend build, UI quality checks, and strict OpenSpec validation.
- [x] 2.3 Re-run frontend validation after the payment-language refinement.
- [x] 2.4 Re-run frontend validation after the payment-flow ordering refinement.
