**GitHub Issue:** #88

## Why

Administrators currently have no way to print a transaction shipping receipt from the transaction detail or transaction list. A reusable, stored PDF based on the latest Ginee preview will support both individual and batch operations while avoiding unnecessary regeneration of unchanged receipts.

## What Changes

- Add a transaction receipt-printing capability under the monorepo module structure.
- Add an individual print action to the transaction detail page.
- Add a bulk print action to the transaction list that validates transaction statuses before processing.
- Prevent printing for transactions with `cancelled` status.
- Render a shipping receipt following the latest Ginee preview, using data already available on the transaction, customer, shipping, and product records.
- Persist each generated PDF on the configured storage disk and reuse it on later downloads.
- Package eligible batch receipts into a downloadable ZIP and report skipped cancelled transactions.
- Keep template configuration controls, selectable logos/information, print-size settings, and template variants out of scope for this change.
- Use the latest Ginee preview supplied for this Issue as the visual reference: https://cdn-oss.ginee.com/official/wp-content/uploads/2023/01/image-1-1.png

## Capabilities

### New Capabilities

- `transaction-receipt-printing`: Generate, persist, download, and batch package transaction shipping receipts with cancellation safeguards.

### Modified Capabilities

None.

## Impact

- Adds module code under `app/Modules/TransactionReceipt` for receipt generation, storage reuse, and batch coordination.
- Updates the Filament transaction detail and list surfaces to delegate to the module.
- Adds a receipt view and PDF rendering dependency or adapter if the existing application stack does not provide one.
- Uses the configured upload/storage disk; no transaction status or payment behavior changes are intended.
- Adds focused automated coverage and visual PDF verification artifacts.
