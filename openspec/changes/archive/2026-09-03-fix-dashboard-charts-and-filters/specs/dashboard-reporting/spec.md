## Purpose

Provides administrators with reliable, filterable dashboard reports for sales performance, popular products, and order status.

## ADDED Requirements

### Requirement: Dashboard charts render valid report data

The dashboard SHALL render sales trend, top-products, and order-status charts when the report contains data, and SHALL render an empty but valid chart state when no matching data exists.

#### Scenario: Charts render with matching data

- **WHEN** an administrator opens the dashboard for a period containing completed orders
- **THEN** the sales trend, top-products, and order-status visualizations are displayed without client-side chart errors

#### Scenario: Charts render with no matching data

- **WHEN** an administrator selects filters for which no report data exists
- **THEN** each visualization remains structurally rendered with zero or empty values and the page does not fail

### Requirement: Dashboard supports category and order-status filters

The dashboard SHALL allow administrators to filter relevant reports by product category and transaction order status, using available category records and the transaction status choices.

#### Scenario: Filter by category and order status

- **WHEN** an administrator selects a category and an order status
- **THEN** dashboard summaries and report data include only matching products and transactions while preserving the selected date range

#### Scenario: Clear product filters

- **WHEN** an administrator clears category and status filters
- **THEN** the dashboard returns to reporting across all product categories and transaction statuses for the selected date range

### Requirement: Dashboard presents order status horizontally

The order-status visualization SHALL use horizontal bars with readable status labels and counts.

#### Scenario: View order status report

- **WHEN** an administrator views the order-status section
- **THEN** statuses are presented as horizontal bars rather than radial segments

### Requirement: Report filter panel is concise

The dashboard SHALL retain date controls while omitting the report-range panel title and explanatory description.

#### Scenario: View dashboard filters

- **WHEN** an administrator opens the dashboard
- **THEN** date, category, and transaction-status controls are available without the removed title and description text
