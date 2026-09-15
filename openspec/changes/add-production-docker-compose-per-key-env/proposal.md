## Why

GitHub Issue: #3

The repository currently provides Sail for development and an optional FrankenPHP runtime, but it does not provide a reproducible production Compose stack. The reference deployment also stores the complete production environment as one multiline secret, which makes individual configuration changes, rotation, and auditing unnecessarily coarse. This change establishes a production runtime with independently managed values and explicit OPcache settings for the 2 CPU / 2 GB target.

## What Changes

- Add a separate production Docker Compose manifest for FrankenPHP/Octane, queue worker, scheduler, MySQL, and optional Redis with healthchecks, resource limits, restart policies, and persistent volumes.
- Add a multi-stage production image that installs only production Composer dependencies and builds the Vite assets.
- Render `.env.production` from one GitHub Environment value per configuration key instead of a single `PRODUCTION_ENV` blob.
- Add OPcache configuration keys and production values to the environment example, runtime image, Compose wiring, and documentation.
- Update the production deployment workflow and documentation with VPS setup, deployment, scheduler, worker, rollback, backup, and safe-volume procedures.
- Keep the existing development Sail and opt-in FrankenPHP workflows unchanged.

## Capabilities

### New Capabilities

- `platform-production-runtime`: Runs the Laravel application and its supporting services as a resource-bounded production Docker stack with independently managed environment values.

### Modified Capabilities

None.

## Impact

- Adds `docker-compose.production.yml`, production Docker build files, and a production environment example.
- Changes the production GitHub Actions workflow and its required GitHub Environment variables/secrets.
- Adds deployment documentation and root README guidance.
- Uses the existing Laravel Octane/FrankenPHP dependency and application configuration; no database schema or public application API changes are expected.
