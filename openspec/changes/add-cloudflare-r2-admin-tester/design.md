## Context

The repository already uses Laravel filesystem disks and has the AWS S3 Flysystem adapter in the dependency set. The current worktree contains an uncommitted `r2` disk configuration; this change must complete the documented environment contract and add an admin verification surface without changing existing upload behavior.

## Goals / Non-Goals

**Goals:**

- Keep R2 credentials exclusively in environment-backed configuration.
- Encapsulate the temporary object lifecycle in an application service so the Filament page remains a UI adapter.
- Provide an authenticated, permission-aware Filament page with localized safe feedback.
- Make cleanup run even when write, read, or content verification fails.

**Non-Goals:**

- Switching the application default disk or migrating existing media uploads.
- Persisting test history or test objects.
- Adding a credential-management UI or database settings.

## Decisions

### Use the existing Laravel filesystem abstraction

The service will resolve the named `r2` disk through Laravel's filesystem manager and perform put, read, content comparison, and delete operations through that abstraction. This keeps the tester aligned with the same S3-compatible adapter used by the application and avoids coupling the admin page to the AWS SDK client.

Alternative considered: calling the Cloudflare R2 API directly. This would duplicate provider behavior and bypass the application's configured filesystem contract.

### Use a unique, namespaced temporary object

Each test will generate a unique identifier under a dedicated test prefix and write a small deterministic payload containing a random token. The path is not user-provided, preventing path traversal and reducing collision risk during concurrent tests.

Alternative considered: a fixed health-check object. A fixed name creates collisions and can leave stale objects after interrupted requests.

### Always attempt cleanup in a finally path

The service will attempt deletion after the object is created, including when read or content verification fails. A cleanup failure will make the overall result unsuccessful and will be reported without exposing the provider exception.

Alternative considered: scheduled cleanup. That would leave temporary objects and introduce unnecessary queue or scheduler dependencies for a synchronous diagnostic action.

### Keep the Filament page thin and permission-aware

The page will call the service, convert the result into localized Filament notifications, and enforce admin authentication plus a dedicated Shield page permission. It will not display environment values or raw exception messages.

Alternative considered: putting storage operations directly in the page action. That would make the integration harder to test and mix provider error handling with presentation code.

## Risks / Trade-offs

- [R2 permissions or bucket policy are incomplete] → Report a generic failed lifecycle status and cover failure handling with mocked filesystem tests.
- [A request is interrupted after object creation] → Use a recognizable namespaced prefix and document that the synchronous finally cleanup is best-effort; do not add persistent history in this scope.
- [Provider exceptions contain secrets] → Log only sanitized failure metadata if logging is needed, and expose only localized generic messages to administrators.
- [A delete failure leaves an object] → Treat cleanup failure as a failed test and include the generated object key only in internal test diagnostics, never in user-facing output.

## Migration Plan

1. Deploy the dependency/configuration and documented environment variable names.
2. Set R2 credentials and bucket/endpoint values in the runtime environment.
3. Clear Laravel configuration cache if the deployment uses cached configuration.
4. Open the admin R2 tester and run the lifecycle test.
5. Roll back by removing the tester page/service and R2 disk configuration; no database rollback is required.
