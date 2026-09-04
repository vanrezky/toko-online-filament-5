## 1. Module and Dependencies

- [x] 1.1 Create the `app/Modules/TransactionReceipt` module structure for data objects, services, exceptions, and receipt rendering support.
- [x] 1.2 Confirm the installed PDF, QR, and barcode capabilities; add only the required direct Composer dependencies and update the lock file when the existing stack does not provide them.

## 2. Receipt Data and PDF Rendering

- [x] 2.1 Create a receipt data mapper that eagerly loads customer, address, products, shipping details, courier assets, and store settings while preserving transaction snapshot fields and safe fallbacks.
- [x] 2.2 Implement the Ginee-inspired shipping-label Blade view using prepared data only, including store/receiver sections, tracking and order identifiers, shipping information, notes, product rows, barcode, and QR content.
- [x] 2.3 Implement centralized eligibility checking so `TransactionStatus::cancelled` cannot be rendered or downloaded, including non-Filament callers.
- [x] 2.4 Implement stable transaction-specific storage keys on the configured upload disk and a receipt service that returns existing files without invoking the renderer.
- [x] 2.5 Implement missing-file PDF generation, storage, and download responses, including local filesystem-safe resolution for optional logos and generated barcode/QR assets.

## 3. Batch Receipt Processing

- [x] 3.1 Implement a batch service that checks every selected transaction status before generating, reading, or packaging any receipt.
- [x] 3.2 Reuse existing eligible PDFs and generate only missing eligible PDFs, never including cancelled transactions.
- [x] 3.3 Package eligible receipts into a temporary ZIP with stable filenames, clean up the temporary archive on success and failure, and return eligible/skipped counts to the caller.

## 4. Filament Integration

- [x] 4.1 Add the individual print action to `ViewTransaction` and make it visible only for eligible transaction records.
- [x] 4.2 Add the batch print action to `TransactionResource` using the existing transaction selection toolbar pattern and delegate all processing to the module.
- [x] 4.3 Add Indonesian admin translations and clear success, skipped, empty-selection, and failure notifications for individual and batch printing.

## 5. Automated Coverage

- [x] 5.1 Add module tests for eligibility, prepared receipt data, stable storage reuse, and missing-file generation using fake storage and an isolated renderer boundary.
- [x] 5.2 Add batch tests covering mixed eligible/cancelled selections, all-cancelled selections, existing/missing files, and the no-regeneration guarantee.
- [x] 5.3 Add Filament-facing regression coverage proving the individual action is unavailable for cancelled transactions and the bulk action delegates status filtering.

## 6. Verification and Traceability

- [x] 6.1 Run focused PHPUnit tests, PHP lint, Pint, and `openspec validate add-transaction-receipt-printing --type change --strict`.
- [x] 6.2 Generate a representative receipt and batch ZIP, reopen the PDF/ZIP, and inspect the rendered label visually for clipping, overlap, missing identifiers, and unreadable barcode/QR output.
- [x] 6.3 Verify the implementation against Issue #88 and every scenario in the transaction-receipt-printing specification before handoff for Pull Request creation.
