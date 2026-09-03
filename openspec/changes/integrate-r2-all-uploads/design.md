## Context

See `proposal.md` and `specs/r2-persistent-upload-storage/spec.md`. The current `dev` branch has persistent uploads spread across Filament file components, Spatie Media Library calls, custom temporary-file handling, frontend controllers, and a URL importer. The tester work from Issue #84/PR #85 is not present in this branch's filesystem configuration, while two unrelated user edits are currently uncommitted and must remain untouched.

## Goals / Non-Goals

**Goals:**

- Establish one explicit R2 destination for all persistent user-facing uploads identified in Issue #86.
- Keep existing media collections, paths, validation, conversions, and retrieval behavior compatible.
- Make the disk choice testable and prevent partial media state on failed persistence.
- Add focused automated coverage for each upload family and verify the admin flows where practical.

**Non-Goals:**

- Bulk migration of historical local/public media.
- Moving Livewire temporary files or Excel import staging files to permanent R2 storage.
- Moving generated transaction snapshots, regional exports, or regional seeder files.
- Changing public URL shape, access policy, image transformations, or unrelated filesystem defaults.

## Decisions

### Use an explicit persistent-upload disk

Persistent upload entry points will select the configured R2 disk explicitly rather than changing the application's global default filesystem. This limits the blast radius for temporary files, generated files, and existing code that intentionally selects another disk.

Alternative considered: changing `FILESYSTEM_DISK` globally. Rejected because it would implicitly alter temporary uploads, operational files, and any unclassified writes.

### Reuse the R2 disk configuration from the tester change

The implementation will use the environment-backed `r2` disk and existing R2 dependency/configuration introduced for Issue #84/PR #85. If that PR is not merged into `dev` when implementation starts, the missing disk/dependency pieces must be brought into this branch as a prerequisite, without changing the tester's product scope.

Alternative considered: creating a second storage configuration for this feature. Rejected because it would duplicate credentials and increase configuration drift.

### Preserve media-library contracts

Filament media fields, direct `addMedia*` calls, and custom variant handling will pass the R2 destination at the persistence boundary. Existing collection names, generated paths, and conversion registration remain unchanged. Existing media will continue to resolve from its recorded disk; no migration job is included.

Alternative considered: rewriting media URLs or migrating all historical objects. Rejected because it adds operational risk and is not required for new uploads to work.

### Keep staging local and final persistence remote

Livewire temporary upload handling and Excel review staging stay on their existing temporary/local lifecycle. Only a file that becomes a persistent user-facing asset uses R2.

Alternative considered: placing all temporary uploads on R2. Rejected because temporary cleanup and import review do not require durable media and would increase object churn and cleanup complexity.

### Use failure-safe persistence boundaries

Tests will assert that failed R2 writes do not leave a successful association or an orphaned media record. Existing validation and exception paths will be preserved unless a narrow cleanup/transaction adjustment is required to meet this guarantee.

## Risks / Trade-offs

- [Risk] A remote R2 failure can make an upload slower or unavailable. → Mitigation: retain existing validation, surface the existing failure path, and add failure-focused tests.
- [Risk] Some generic file fields currently inherit a default disk. → Mitigation: enumerate every persistent field and add explicit R2 selection; leave temporary fields explicit/local.
- [Risk] Historical media may live on local/public storage. → Mitigation: preserve recorded media disks and defer migration to a separate approved change.
- [Risk] PR #85 may not be merged before this branch is reviewed. → Mitigation: verify the branch dependency before implementation and include only missing prerequisite configuration if necessary.

## Migration Plan

1. Verify the R2 disk configuration and package from Issue #84/PR #85 are available on the implementation base.
2. Deploy code with environment variables configured and validate the admin R2 tester.
3. Upload one representative asset from each persistent surface and verify object placement and application URL resolution.
4. Roll back the code commit if needed; existing historical media remains readable because no records or objects are migrated.

