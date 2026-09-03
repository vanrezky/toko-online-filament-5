## Why

The admin dashboard's sales trend and top-products charts fail to render because their Chart.js axis options are invalid. The dashboard also lacks the requested product category and product status filters, making reports harder to narrow and interpret.

## What Changes

- Fix chart axis configuration so the sales trend and top-products charts render without Chart.js errors.
- Change the orders-by-status visualization from a doughnut chart to a horizontal bar chart.
- Remove the report-range panel title and description.
- Add product category filtering backed by `App\Models\Category`.
- Add order status filtering backed by `App\Enums\TransactionStatus`.
- Apply the product filters to the dashboard summaries and product/order report queries where relevant.
- Add regression coverage for chart data and dashboard filters.

## Capabilities

### New Capabilities

- `dashboard-reporting`: Admin dashboard charts, date filtering, product category/status filtering, and report presentation.

### Modified Capabilities

None.

## Impact

- Affects `Dashboard`, dashboard chart widgets, and `DashboardStats` query logic.
- No database schema, payment, transaction-status, or public API changes.
- Playwright verification should confirm the charts render without console errors on `/admin/dashboard`.
- GitHub Issue: #80.
