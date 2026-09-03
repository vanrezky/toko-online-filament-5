## Why

Cloudflare R2 is configured and has an admin connectivity tester, but persistent file uploads still resolve through a mixture of local/default disks and media-library defaults. This makes storage behavior inconsistent across the admin and customer-facing upload flows.

This change implements the approved scope from [Issue #86](https://github.com/vanrezky/toko-online-filament3/issues/86): route persistent user-facing uploads through the configured R2 disk while preserving temporary-upload behavior and existing media contracts.

## What Changes

- Configure every persistent user-facing upload surface to store its final file in R2.
- Cover admin Filament uploads for products, variants, categories, vouchers, sliders, blog posts, CMS pages, customers, website settings, and product review/import-related flows.
- Cover frontend profile uploads, product review images, and product image imports from public URLs.
- Preserve existing media collections, validation rules, previews, conversions, generated paths, and URL access behavior.
- Keep Livewire temporary files and the Excel import temporary upload local/ephemeral unless they are later persisted as a user-facing asset.
- Explicitly leave generated transaction snapshots, regional exports, and seeder output outside this storage migration.
- Add regression coverage for storage target selection, persistent upload paths, and failure-safe behavior.

## Capabilities

### New Capabilities

- `r2-persistent-upload-storage`: Persistent user-facing uploads use Cloudflare R2 consistently across all detected upload features.

### Modified Capabilities

- None.

## Impact

- Affected backend code includes Filament resources/pages, frontend controllers, the product image URL importer, filesystem configuration, and upload-related tests.
- Existing records remain readable from their current media disk; this change does not perform a bulk migration of historical files.
- The R2 disk and credentials remain environment-driven. The current `dev` branch does not yet contain the R2 disk configuration from the tester PR, so implementation must account for that prerequisite before applying upload changes.
