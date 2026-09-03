## Context

See proposal.md and the dashboard-reporting spec. The dashboard widgets share a service that builds completed-order report queries, while Filament page filters are passed to each widget through Livewire. Playwright reproduced the rendering failure with `this.options.ticks.setContext is not a function`; the existing chart options use empty arrays for axis ticks.

## Goals / Non-Goals

**Goals:**

- Make all three dashboard charts compatible with the installed Chart.js/Filament widget rendering behavior.
- Keep filter parsing centralized so every widget receives the same date, category, and transaction-status constraints.
- Preserve the existing completed-order date semantics and transaction status report behavior.

**Non-Goals:**

- Database migrations or changes to model schemas.
- Changing transaction status definitions or payment behavior.
- Reworking the dashboard layout beyond the requested filter text and chart orientation.

## Decisions

- Apply the category constraint at the transaction-product/product join level and the order-status constraint at the transaction join level. This keeps product-specific filters accurate while ensuring order status matches the persisted transaction status.
- Use category IDs from `Category` records and enum values from `TransactionStatus` in the page form. This matches the statuses persisted in `transactions.status` and avoids hardcoded order-status data.
- Replace invalid empty `ticks` arrays with valid Chart.js options such as `display: false`, retaining hidden axis ticks without passing an invalid object shape to Chart.js.
- Change the order-status chart type to `bar` and set `indexAxis` to `y`; retain the existing enum-derived labels, counts, and colors.
- Include all filter values in managed cache keys so filtered results cannot reuse an unfiltered or differently filtered result.

## Risks / Trade-offs

- [Risk] Product filters may make order-level totals differ from totals for all orders → document and test that product-related summaries are scoped to matching products.
- [Risk] Long category names may crowd horizontal labels → preserve responsive chart sizing and rely on the existing chart container behavior.
- [Risk] Invalid or stale filter values could reach the service → normalize/filter values and treat missing values as no constraint.

## Migration Plan

No database migration is required. Deploy the application code and clear the managed dashboard cache if stale entries remain; rollback is a code revert.
