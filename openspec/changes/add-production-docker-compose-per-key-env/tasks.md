## 1. Production image

- [x] 1.1 Add production Docker ignore rules that exclude development dependencies, local environment files, tests, and mutable runtime data from the image context.
- [x] 1.2 Add a multi-stage production Dockerfile that installs production Composer dependencies, builds Vite assets with Node 22, installs required PHP extensions, and prepares Laravel runtime directories.
- [x] 1.3 Add the production entrypoint that renders the environment-driven OPcache INI values and starts Octane, queue, scheduler, or one-off Artisan commands.

## 2. Production Compose runtime

- [x] 2.1 Add `docker-compose.production.yml` with FrankenPHP app, queue worker, scheduler, MySQL, and optional Redis services.
- [x] 2.2 Configure healthchecks, restart policies, direct FrankenPHP HTTP/HTTPS ports, named persistent volumes, service dependencies, and the 2 CPU / 2 GB resource budget.
- [x] 2.3 Configure MySQL and optional Redis defaults for bounded connections, memory, and persistence without exposing database root credentials to application processes.

## 3. Environment and deployment

- [x] 3.1 Add `.env.production.example` with one key/value per line, including documented `OPCACHE_*` values and production service defaults.
- [x] 3.2 Replace the disabled host-based deployment workflow with immutable GHCR image build/publish and Compose deployment using individual GitHub Environment values.
- [x] 3.3 Validate required configuration values before upload, render `.env.production` without logging values, apply restrictive permissions, and remove the runner copy after deployment.
- [x] 3.4 Update the Issue status to `status:in-progress` when implementation starts and link the OpenSpec change to Issue #3.

## 4. Documentation and verification

- [x] 4.1 Document GitHub Environment variable/secret names, OPcache values, first-time VPS setup, deployment, scheduler, worker, and health verification.
- [x] 4.2 Document immutable image rollback, external database backup expectations, and the prohibition on deleting persistent Docker volumes.
- [x] 4.3 Validate the OpenSpec change, production Compose interpolation, production image build, frontend tests/build, and repository diff hygiene.
