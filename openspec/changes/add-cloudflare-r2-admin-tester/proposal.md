## Why

GitHub Issue: #84

Cloudflare R2 configuration is being introduced, but administrators currently have no safe, visible way to confirm that the configured credentials, bucket, endpoint, and S3-compatible filesystem can perform a complete operation. A temporary end-to-end test in the admin panel will reduce configuration uncertainty before R2 is used by application uploads.

## What Changes

- Add the R2 environment variables and S3-compatible `r2` filesystem configuration.
- Add an authenticated Filament page for testing the R2 connection.
- Make the test write a unique temporary object, read it back, verify its contents, and remove it.
- Show a safe success or failure notification without exposing credentials or sensitive configuration.
- Add service-level and admin regression coverage for successful and failed tests.

## Capabilities

### New Capabilities

- `r2-storage-testing`: Environment-backed Cloudflare R2 storage configuration and an admin-only, temporary-object connectivity test.

### Modified Capabilities

None.

## Impact

- Affected Laravel filesystem configuration and environment example.
- New application service, Filament page/view, translations, and tests.
- Uses the existing Flysystem AWS S3 v3 adapter already present in the worktree.
- No database schema, public API, or existing upload migration is included.
- Existing unrelated worktree changes must remain untouched.
