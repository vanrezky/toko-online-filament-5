## Context

The default `laravel.test` service is built from the Laravel Sail PHP 8.3 image and serves the application with `artisan serve`. The same image already contains the normal PHP extensions used by the project, but it does not contain the FrankenPHP or Caddy binary. Vite remains a host-managed process, while MySQL and Redis are existing Compose dependencies.

See `proposal.md` for the motivation and `specs/development-octane-frankenphp-runtime/spec.md` for the behavior contract.

## Goals / Non-Goals

**Goals:**

- Add a repeatable FrankenPHP-backed Octane runtime for local production-like testing.
- Keep the current Sail service, ports, volumes, and Vite workflow as the default path.
- Make runtime selection explicit and reversible through Compose and Make commands.
- Detect request-state leaks with focused tests and repeated-request smoke checks.

**Non-Goals:**

- Replace the existing Sail image or production deployment.
- Add Nginx or a second reverse proxy.
- Introduce Swoole, RoadRunner, or formal benchmarking infrastructure.
- Change application business behavior or database schema.

## Decisions

### Use a separate FrankenPHP image

Create a project-owned FrankenPHP Dockerfile based on the official FrankenPHP PHP 8.3 image. Install only the PHP extensions and Composer runtime required by the locked application dependencies, then run the mounted application through `php artisan octane:start --server=frankenphp`.

This is preferred over modifying the vendored Sail runtime because it keeps the FrankenPHP binary and its PHP runtime explicit, avoids editing ignored vendor files, and leaves the default Sail image reproducible. A separate PHP-FPM plus Nginx stack is rejected because FrankenPHP already provides the HTTP server and the goal is to exercise the Octane FrankenPHP driver directly.

### Add an opt-in Compose profile

Add a dedicated FrankenPHP service under an explicit Compose profile with its own configurable host port. The service shares the existing MySQL and Redis services and waits for their health checks, but it does not publish the host Vite port. The default `laravel.test` service remains outside the profile and keeps its current command.

This is preferred over replacing `laravel.test` or maintaining a second full Compose file because one base topology keeps dependency and volume definitions aligned while the profile makes the runtime choice visible and reversible.

### Keep worker and scheduler processes separate

Horizon and `schedule:work` remain separate processes invoked through the existing development workflow. FrankenPHP serves HTTP requests only; it does not run queue or scheduler processes inside the web worker. This avoids mixing unrelated long-running lifecycles and preserves the current operational model.

### Treat request state as worker state

Review the existing scoped services, tracing stack, correlation context, and runtime configuration mutation. Fix only concrete persistence risks found by the audit, using request-scoped bindings or `try/finally` cleanup where applicable. Add repeated-request checks so a failed request cannot leave active tracing or correlation state for the next request.

This is preferred over a broad application refactor because Laravel already provides scoped lifetimes and the change needs only the smallest safeguards required by the new runtime.

### Document the learning workflow

Add Make targets or equivalent commands for building, starting, checking logs/status, stopping, and returning to the default Sail mode. Documentation will explain that Octane keeps the application booted, FrankenPHP is the HTTP runtime, and persistent workers require state isolation.

## Risks / Trade-offs

- **[Risk] FrankenPHP image lacks an extension required by the application.** → Validate with `composer check-platform-reqs` inside the image and add only missing extensions to the Dockerfile.
- **[Risk] Request-specific state survives between requests.** → Use scoped services, explicit cleanup, and repeated-request smoke checks covering correlation and tracing behavior.
- **[Risk] FrankenPHP and default Sail claim conflicting host ports.** → Use a separate configurable port and never publish the host Vite port from the runtime service.
- **[Risk] The additional image increases local build time and disk usage.** → Keep it opt-in and share the application bind mount and dependency services.
- **[Risk] Composer resolution exposes existing advisories or changes unrelated packages.** → Review the lock diff and run the project test suite; do not broaden dependency updates beyond Octane's requirements.

## Migration Plan

1. Add and lock `laravel/octane`, build the FrankenPHP image, and validate its platform requirements.
2. Start the optional profile and run the documented smoke checks against the configured port.
3. Keep normal development on the existing Sail command unless FrankenPHP behavior is being tested.
4. Roll back by stopping the optional profile and removing only the Octane-specific dependency, Dockerfile, Compose service, commands, documentation, and tests in the feature branch.
