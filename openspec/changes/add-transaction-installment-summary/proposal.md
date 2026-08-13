## Why

Issue #27 requires admins to understand an installment obligation from the transaction detail without navigating away for its basic terms and progress.

## What Changes

- Add an installment-only summary to the admin transaction detail.
- Link the summary to the existing installment detail for payment schedule and administration.
- Add focused regression coverage for visible installment data and hidden non-installment data.

## Capabilities

### New Capabilities

- `admin-transaction-installment-summary`: Show an installment summary and drill-down link from an installment transaction.

### Modified Capabilities

- None.

## Impact

- `ViewTransaction` admin infolist and focused Filament feature tests.
- No database, calculation, payment, or schedule changes.
