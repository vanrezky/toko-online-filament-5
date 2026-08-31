## 1. Module and data contracts

- [x] 1.1 Create the `App\\Modules\\ProductImport` module structure for services, imports, exports, data/value objects, and exceptions.
- [x] 1.2 Define the validated-row, preview, and structured validation-error contracts used between the module and Filament UI.
- [x] 1.3 Confirm product, category, warehouse, user, and existing image-import contracts against current models and schema without adding database columns.

## 2. Template and validation

- [x] 2.1 Implement the Excel template export with `Produk` and `Kategori dan Gudang` sheets, required/optional headers, and numbered name-only references.
- [x] 2.2 Implement upload format/size/row validation and map category and warehouse names to internal IDs.
- [x] 2.3 Validate required fields by product type, supported product types, numeric values, optional fields/image URLs, and duplicate/ambiguous reference names with row/column errors.
- [x] 2.4 Generate product codes for blank codes during validation or the server-side submit preparation according to the approved contract.

## 3. Preview cache and one-time submit

- [x] 3.1 Store validated rows, owner identity, and expiry metadata under a UUID/token with a one-hour TTL.
- [x] 3.2 Implement token ownership, expiry, and cache-lock checks for submit requests.
- [x] 3.3 Create products inside a transaction from cached data only, and remove token/cache only after successful completion.
- [x] 3.4 Preserve an unexpired preview after a failed transaction and reject reused, invalid, expired, or concurrently consumed tokens.
- [x] 3.5 Integrate the existing product image URL importer for optional image URLs without changing its existing contract.

## 4. Filament integration and translations

- [x] 4.1 Add download-template, upload/validate, review, and submit actions to the product list/resource while keeping business logic in the module.
- [x] 4.2 Render validated rows and row/column errors in the review step, including the one-hour expiry notice.
- [x] 4.3 Add Indonesian and English translation keys for actions, fields, validation errors, expiry, and submit outcomes.
- [x] 4.4 Ensure submit sends only the server-issued token and does not send the validated product payload again.

## 5. Automated verification

- [x] 5.1 Add export tests for both sheets, required headers, and category/warehouse reference layout.
- [x] 5.2 Add validation tests for required fields, numeric/type/URL rules, name matching, generated codes, and row/column errors.
- [x] 5.3 Add cache/token tests for TTL metadata, ownership, expiry, one-time use, concurrent submission, cleanup, and failed-transaction retry.
- [x] 5.4 Add product creation tests proving submit uses cached data and optional image URLs follow the existing importer contract.
- [x] 5.5 Run focused PHPUnit tests, frontend/build checks as applicable, and `openspec validate --strict`.
