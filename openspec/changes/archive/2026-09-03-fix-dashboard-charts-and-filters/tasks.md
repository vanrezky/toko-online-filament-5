## 1. Dashboard filter contract

- [x] 1.1 Add category and transaction-status controls to the dashboard filter form using server-backed category options and `TransactionStatus` values.
- [x] 1.2 Remove the report-range section title and description while retaining date validation and defaults.

## 2. Report query and cache behavior

- [x] 2.1 Normalize date, category, and transaction-status filters in the dashboard statistics service.
- [x] 2.2 Apply category and transaction-status filters to dashboard aggregates and order/product queries without changing date semantics.
- [x] 2.3 Include category and transaction-status values in dashboard cache keys and preserve empty-result behavior.

## 3. Chart rendering

- [x] 3.1 Replace invalid Chart.js axis tick configurations in the sales trend and top-products charts with valid options.
- [x] 3.2 Change the order-status chart to a horizontal bar chart while preserving enum labels, counts, and colors.
- [x] 3.3 Verify chart data remains valid for populated and empty result sets.

## 4. Automated validation

- [x] 4.1 Add focused regression tests for filtered dashboard statistics and cache-key separation.
- [x] 4.2 Add or update Playwright coverage for admin dashboard chart visibility and absence of the reproduced console error.
- [x] 4.3 Run formatter, focused tests, and frontend/build validation; record results against Issue #80.
