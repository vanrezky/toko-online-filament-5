## Context

See `proposal.md` for the motivation and scope. The transaction administration surface already has individual header actions in `ViewTransaction` and selection-based `BulkAction` support in `TransactionResource`. Transaction status is enum-cast, transaction products contain historical snapshot fields, courier assets exist under `public/assets/images/courier`, and store identity is available through `GeneralSettings`.

The current Composer manifest does not provide an application PDF renderer. The receipt must also work with the configured persistent upload disk, including the existing object-storage convention, and must not put business calculations in the Blade view.

## Goals / Non-Goals

**Goals:**

- Encapsulate receipt preparation, PDF rendering, storage reuse, and batch coordination in `app/Modules/TransactionReceipt`.
- Keep Filament pages thin: individual and bulk actions delegate to the module and translate the result into downloads and notifications.
- Produce a compact Ginee-inspired shipping label with stable fallbacks for optional fields.
- Make the first generated PDF a persistent transaction snapshot so later downloads do not regenerate it.
- Validate all selected statuses before any batch receipt is generated or packaged.

**Non-Goals:**

- Building a configurable template editor or settings model.
- Supporting multiple paper sizes, template variants, or per-field visibility controls.
- Calling courier APIs or generating courier-specific shipping labels beyond the available application data.
- Changing transaction, payment, or shipping state.

## Decisions

### 1. Put the feature in a dedicated transaction-receipt module

Use a dedicated module with services and data objects under `app/Modules/TransactionReceipt`. Keep the Filament integration in the existing resource/page files, but have it call the module service. This follows the repository's module convention and keeps PDF/storage concerns out of the page classes.

Alternative considered: place all logic in `ViewTransaction` and `TransactionResource`. Rejected because it would duplicate the individual/batch path and make the receipt behavior difficult to test independently.

### 2. Use a deterministic storage key instead of a database column

Store receipts at a stable transaction-specific path such as `receipts/transactions/{transaction-uuid}.pdf` on `config('filesystems.upload_disk')`. The service checks existence before rendering and treats the stored PDF as the immutable snapshot for that transaction.

Alternative considered: add an invoice path, version, or generated timestamp column to `transactions`. Rejected for the initial feature because the deterministic key provides reuse without a schema migration; metadata can be introduced later if template versioning becomes a requirement.

### 3. Render a prepared data object through a dedicated Blade view

The module will load the required relationships and map them into a receipt data object before rendering. The Blade view will only display prepared values, including product snapshot fallbacks, address lines, courier data, payment/COD display, and barcode/QR image payloads.

Use the application's configured store identity and existing courier assets where available. Missing optional assets or values must resolve to safe fallbacks so PDF rendering remains valid.

Alternative considered: read models and calculate totals directly inside the Blade view. Rejected because it mixes business logic with presentation and risks inconsistent transaction totals.

### 4. Use a server-side PDF renderer and generated barcode assets

Add an application-supported HTML-to-PDF renderer through Composer, preferring the Laravel Dompdf bridge already compatible with this Laravel version. Use the existing QR-code package when available and add a direct barcode generator dependency if required to render the tracking/order barcode as an embeddable image. Verify the actual generated PDF rather than relying only on PHP or Blade syntax checks.

Alternative considered: return a browser print page. Rejected because the requirement is to persist a reusable PDF and to package multiple receipts into a ZIP.

### 5. Package batch output as a temporary ZIP

The batch service will first partition selected records by status, then ensure eligible receipt files exist, add each eligible PDF once to a temporary ZIP, and return a download response. The temporary archive will be removed after it is sent. Existing receipt files will be read from the configured disk; cancelled records will never be read or generated.

Alternative considered: merge all receipts into a single PDF. Rejected because the agreed batch behavior is one receipt per transaction and ZIP packaging preserves individual files for warehouse operations.

### 6. Keep cancellation checks centralized

The module will expose one eligibility predicate based on `TransactionStatus::cancelled`, and both individual and batch flows will use it. The Filament visibility check improves UX, while the service check remains authoritative for defense in depth and for future callers.

Alternative considered: rely only on the Filament action visibility. Rejected because bulk requests and non-Filament callers could bypass a UI-only check.

## Risks / Trade-offs

- [Risk] Stored receipts become snapshots and will not reflect later transaction edits. → Treat this as intentional for reuse; document the behavior in the service and defer versioned regeneration to the future template-settings feature.
- [Risk] Object-storage reads or temporary ZIP creation may fail for large batches. → Use streams where supported, keep the batch bounded by selected records, clean temporary files in success and failure paths, and surface a clear notification on failure.
- [Risk] Remote or malformed logo assets can cause PDF rendering failures. → Resolve local application assets to filesystem-readable paths where possible and omit optional images when they cannot be read.
- [Risk] Barcode or QR libraries may not be available in the current installed dependency set. → Confirm package availability before implementation, update `composer.json` and `composer.lock` only when necessary, and add focused rendering coverage.

## Migration Plan

No database migration is planned. Deploy the module, receipt view, dependency changes, and Filament integrations together. Existing transactions have no receipt files and will generate them lazily on first print. Rollback removes the feature code and dependency change; already-generated storage files may remain recoverable and can be pruned separately after confirming no active rollback consumers.
