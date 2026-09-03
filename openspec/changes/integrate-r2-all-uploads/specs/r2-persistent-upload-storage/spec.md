## Purpose

Provide one predictable, environment-configured storage destination for persistent user-facing files throughout the application without changing temporary-upload or generated-export behavior.

## ADDED Requirements

### Requirement: Persistent user-facing uploads use R2

The system MUST store the final files created by persistent user-facing upload features on the configured Cloudflare R2 disk.

The scope MUST include these detected surfaces:

- product main images and product variant images;
- category, voucher, slider, blog post, CMS page, customer profile, and website setting images;
- customer profile image uploads from the account flow;
- product review image uploads on create and update;
- product images imported from public URLs.

#### Scenario: Admin asset upload is persisted to R2

- **WHEN** an authorized administrator uploads a supported persistent image in an admin form
- **THEN** the resulting asset is stored on the configured R2 disk and remains available through the existing media or file URL contract

#### Scenario: Customer asset upload is persisted to R2

- **WHEN** an authenticated customer uploads a profile image or product review image
- **THEN** the resulting asset is stored on the configured R2 disk and remains associated with the same customer, review, and media collection as before

#### Scenario: URL-imported product image is persisted to R2

- **WHEN** a valid public product image URL is imported
- **THEN** the downloaded product image is stored on R2 using the existing product media collection and filename behavior

### Requirement: Existing upload contracts remain compatible

The system MUST preserve existing upload validation, generated paths, media collection names, previews, conversions, image associations, and URL generation unless a change is required solely to select R2.

#### Scenario: Existing media remains readable

- **WHEN** an existing product, review, profile, or admin asset is displayed after the storage change
- **THEN** the application continues to resolve its current media URL without requiring historical files to be migrated

#### Scenario: Upload validation remains enforced

- **WHEN** an uploaded file violates the existing type, size, count, or required-field constraints
- **THEN** the upload is rejected with the existing validation behavior and no persistent R2 asset is left behind

### Requirement: Temporary uploads remain ephemeral

The system MUST keep Livewire temporary uploads and the product Excel import staging file in temporary storage and MUST NOT treat those staging files as persistent R2 assets.

#### Scenario: Excel import staging remains temporary

- **WHEN** an administrator uploads an Excel file for product import review
- **THEN** the file is available to the existing review flow as a temporary file and is not retained as a permanent R2 media asset

#### Scenario: Temporary upload cleanup remains effective

- **WHEN** a temporary upload expires or is discarded
- **THEN** the temporary file is cleaned up according to the existing lifecycle without creating an orphaned R2 object or media record

### Requirement: R2 configuration is environment-only and failure-safe

The system MUST resolve R2 credentials and endpoint settings from environment-backed configuration, MUST NOT expose credentials in application responses or logs, and MUST fail an upload without creating an inconsistent media association when R2 is unavailable.

#### Scenario: Missing R2 configuration is reported safely

- **WHEN** a persistent upload is attempted without valid R2 configuration
- **THEN** the operation fails with an actionable application error and does not expose secret values or leave an orphaned media association

#### Scenario: R2 outage does not create partial state

- **WHEN** R2 rejects or cannot complete a persistent upload
- **THEN** the application reports the failed upload through the existing error path and does not report the asset as successfully attached

### Requirement: Non-user-facing generated files keep their existing disposition

The storage migration MUST NOT change generated transaction product-image snapshots, regional JSON exports, or local regional seeder output unless a separate approved requirement is created.

#### Scenario: Generated operational files are unchanged

- **WHEN** a transaction snapshot, regional export, or regional seed operation runs
- **THEN** it continues to use its existing explicitly selected filesystem behavior and is excluded from the persistent user-upload migration
