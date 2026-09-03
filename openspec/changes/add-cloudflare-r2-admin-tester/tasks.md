## 1. R2 Configuration

- [x] 1.1 Reconcile the existing uncommitted R2 filesystem changes with the approved environment contract and preserve unrelated worktree edits.
- [x] 1.2 Document the R2 environment variables in `.env.example` and verify the S3-compatible adapter dependency is correctly represented in Composer files.

## 2. Connectivity Service

- [x] 2.1 Add an application service that generates a unique namespaced temporary object and writes a deterministic small payload to the `r2` disk.
- [x] 2.2 Complete the read-back and content verification steps, returning a structured success or failure result without sensitive details.
- [x] 2.3 Guarantee best-effort cleanup after object creation and treat cleanup failure as an unsuccessful test result.
- [x] 2.4 Add focused service tests covering successful write/read/verify/delete, operation failures, unique keys, and sanitized failure results.

## 3. Filament Admin Tester

- [x] 3.1 Add a permission-aware Filament admin page under the appropriate platform navigation group with a single R2 test action.
- [x] 3.2 Connect the page action to the service and display localized success/failure notifications without raw provider exception details or credentials.
- [x] 3.3 Add the page view, Indonesian and English translation keys, and Shield permission/discovery configuration following existing admin conventions.
- [x] 3.4 Add regression coverage for page access control and action result handling.

## 4. Verification and Traceability

- [x] 4.1 Run formatter, focused automated tests, and relevant static/syntax checks.
- [x] 4.2 Validate the OpenSpec change and verify each Issue/spec acceptance criterion against the implementation and tests.
- [x] 4.3 Record the final validation status and manual admin test instructions for the Pull Request. Manual verification confirmed through the admin application: the R2 tester runs successfully.
