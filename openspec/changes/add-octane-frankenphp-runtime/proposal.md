## Why

The project currently serves HTTP through Laravel Sail's `artisan serve`, which does not exercise the long-running application lifecycle used by Laravel Octane. Adding an optional FrankenPHP-backed Octane runtime now gives developers a production-like mode for learning and validating persistent workers without disrupting the simpler default Sail workflow.

## What Changes

- Add Laravel Octane as a locked application dependency.
- Add an opt-in FrankenPHP Docker runtime that starts Laravel through Octane.
- Keep the existing Sail runtime as the default development mode.
- Add explicit runtime commands and documentation for starting, stopping, and checking the FrankenPHP mode.
- Add smoke validation for startup, request handling, and repeated requests in a long-running worker.
- Review request-scoped and process-scoped state that could persist between requests.

## Capabilities

### New Capabilities

- `development-octane-frankenphp-runtime`: Optional Laravel Octane and FrankenPHP runtime for production-like development testing.

### Modified Capabilities

## Impact

- `composer.json` and `composer.lock` gain `laravel/octane`.
- Docker Compose gains an opt-in FrankenPHP runtime while preserving the existing Sail service.
- A FrankenPHP-specific Docker image or build definition is added; the current Sail image does not include the FrankenPHP/Caddy binary.
- Development documentation and Make targets are updated with the new runtime workflow.
- Application state handling is reviewed for Octane worker reuse, especially tracing and runtime configuration.
- No production deployment, business behavior, database schema, or public API changes are included.

Issue: #1
