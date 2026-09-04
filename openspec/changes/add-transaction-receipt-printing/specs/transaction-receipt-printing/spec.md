## Purpose

Provide administrators with a reusable shipping-label PDF for eligible transactions, available for individual download and controlled batch download from the transaction administration surfaces.

## ADDED Requirements

### Requirement: Individual receipt printing is restricted to eligible transactions

The system SHALL provide an individual print action for a transaction whose status is not `cancelled`, and SHALL prevent the action from producing a receipt for a `cancelled` transaction.

#### Scenario: Admin prints an eligible transaction

- **WHEN** an administrator requests a receipt for a transaction with a status other than `cancelled`
- **THEN** the system provides the transaction's stored receipt or creates it and provides the generated receipt for download

#### Scenario: Admin opens a cancelled transaction

- **WHEN** an administrator views a transaction with status `cancelled`
- **THEN** the individual print action is unavailable and no receipt is generated or downloaded

### Requirement: Receipt content follows the Ginee preview

The generated receipt SHALL follow the structure of the latest Ginee preview and SHALL display available transaction data for the store, recipient, destination address, order, tracking or receipt code, courier, COD, weight, notes, barcode or QR identifier, and transaction products.

#### Scenario: Receipt is generated from transaction data

- **WHEN** an eligible transaction has its receipt generated
- **THEN** the PDF contains the Ginee-inspired shipping-label sections and uses the transaction's stored customer, address, shipping, tracking, notes, and product data

#### Scenario: Optional transaction data is missing

- **WHEN** an eligible transaction does not have an optional field such as a tracking code, courier logo, note, or product variation
- **THEN** the receipt remains valid and uses an empty or neutral fallback without inventing data

### Requirement: Generated receipts are persisted and reused

The system SHALL persist each generated receipt on the configured storage disk and SHALL reuse the existing file for subsequent individual or batch requests instead of rendering the receipt again.

#### Scenario: Receipt file does not exist

- **WHEN** an eligible transaction is requested and its receipt file is absent
- **THEN** the system renders the receipt, stores the PDF using a stable transaction-specific identity, and returns that file

#### Scenario: Receipt file already exists

- **WHEN** an eligible transaction is requested and its receipt file exists
- **THEN** the system returns the existing file and does not invoke PDF rendering

### Requirement: Batch printing validates statuses before processing

The system SHALL inspect the status of every selected transaction before generating or collecting any batch receipt, SHALL exclude `cancelled` transactions, and SHALL report the number of eligible and skipped transactions.

#### Scenario: Batch contains eligible and cancelled transactions

- **WHEN** an administrator starts a batch receipt request containing both eligible and `cancelled` transactions
- **THEN** the system completes the status check first, includes receipts only for eligible transactions, skips cancelled transactions, and reports the skipped count

#### Scenario: Batch contains only cancelled transactions

- **WHEN** an administrator starts a batch receipt request containing only `cancelled` transactions
- **THEN** the system generates no receipt and returns no ZIP archive while reporting that all selected transactions were skipped

#### Scenario: Batch includes existing and missing receipt files

- **WHEN** an administrator starts a batch receipt request containing eligible transactions with a mixture of existing and missing receipt files
- **THEN** the system reuses existing files, generates only missing files, and returns one ZIP containing one receipt for each eligible transaction
