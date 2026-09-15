## Context

The repository already contains the Laravel Octane dependency, an opt-in FrankenPHP development image, production-oriented OPcache settings, and an existing GitHub Actions deployment entrypoint that is disabled. The target VPS has 2 vCPU and 2 GB RAM, so the production stack must remain bounded and must not alter the bind-mounted Sail workflow. See `proposal.md` and `specs/platform-production-runtime/spec.md` for the motivation and behavior contract.

## Goals / Non-Goals

**Goals:**

- Build one immutable production image containing the Laravel runtime and compiled frontend assets.
- Run HTTP, queue, and scheduled work through FrankenPHP/Octane and dedicated Compose services.
- Keep MySQL and optional Redis persistent and resource-bounded for the target VPS.
- Render `.env.production` from independently managed GitHub Environment values.
- Apply OPcache values at container startup so each environment can tune them without rebuilding the image.
- Make deployment, rollback, and volume safety understandable from repository documentation.

**Non-Goals:**

- Do not replace or reconfigure the existing development Sail or opt-in local FrankenPHP services.
- Do not add a host Nginx or PHP-FPM installation.
- Do not deploy to a live VPS or create real production credentials.
- Do not introduce a database schema or application feature change.

## Decisions

### Separate production image and Compose manifest

Use `docker-compose.production.yml` and `docker/production/` instead of adding production conditionals to the development Compose file. A multi-stage build installs production Composer dependencies, builds Vite assets with Node 22, and copies only the runtime artifacts into the FrankenPHP image. This keeps source bind mounts and development tooling out of the production runtime.

The application service serves ports 80 and 443 directly through FrankenPHP. A dedicated worker runs `queue:work`, and a dedicated scheduler runs `schedule:work`; this keeps all recurring work in Compose and avoids a host crontab as a second scheduler implementation. MySQL is always part of the core stack. Redis uses a Compose profile because the application can operate with file/database drivers on a minimal VPS.

### Resource budget

Declare service-level memory and CPU ceilings sized for the 2 GB / 2 vCPU target: app 512 MB / 1.0 CPU, worker 256 MB / 0.5 CPU, scheduler 128 MB / 0.25 CPU, MySQL 512 MB / 0.5 CPU, and Redis 96 MB / 0.25 CPU when enabled. MySQL also receives a 256 MB InnoDB buffer pool, 30 maximum connections, and disabled performance schema to leave headroom for the OS and Docker runtime.

Higher limits were considered, but they would allow the combined stack to crowd out the host. More workers were also considered; the measured 2-CPU FrankenPHP runtime showed that three workers worsened tail latency, so worker scaling remains an operator-level choice after measurement.

### One configuration key per GitHub Environment value

Use GitHub Environment `secrets` for sensitive values and `vars` for non-sensitive tunables, then render an allowlisted dotenv file in the workflow. The renderer writes each key exactly once, validates required values before upload, does not use a `PRODUCTION_ENV` aggregate secret, and never echoes the generated file. This gives individual rotation and audit boundaries without allowing arbitrary secret names or shell evaluation.

### Runtime OPcache rendering

Keep the OPcache directives in an entrypoint-generated PHP INI file. The production env example exposes `OPCACHE_*` keys for enablement, CLI behavior, memory, interned strings, accelerated files, timestamp validation, revalidation, saved comments, and JIT. The entrypoint writes these values before starting the app, worker, scheduler, or one-off migration command. Immutable production images default to `validate_timestamps=0`; changing PHP code therefore requires an image rebuild/redeploy or service recreation with the new image.

### Immutable deployment and persistent data

Tag the image with the commit SHA and also maintain a convenience `production` tag. The workflow uploads only the Compose manifest and generated env file, pulls the requested immutable tag, waits for MySQL health, runs migrations with `--force`, then starts the complete stack. Rollback changes `IMAGE_TAG` to a known-good SHA and recreates services without removing named volumes. Application storage and FrankenPHP certificate/config data remain in named volumes; database backups are external to Docker volume cleanup.

## Risks / Trade-offs

- [Production secrets are missing or malformed] → Validate required keys before upload, document every key, and fail with the key name only.
- [A PHP source change is hidden by disabled timestamp validation] → Document image rebuild/redeploy requirements and keep watcher behavior limited to development.
- [The 2 GB host is overcommitted] → Declare service caps, keep Redis optional, limit MySQL connections, and monitor actual memory before increasing limits.
- [A queue or scheduler process exits] → Give each service an automatic restart policy and healthcheck, and document `docker compose ps`/logs checks.
- [A rollback removes persistent data] → Use immutable image tags and explicitly prohibit `docker compose down -v` and volume pruning in deployment documentation.

## Migration Plan

1. Create the GitHub `production` Environment and add the documented deployment SSH values plus one `secret` or `variable` for each application configuration key.
2. Configure the VPS deploy directory, Docker Engine/Compose plugin, firewall ports 80/443, and DNS for the FrankenPHP host.
3. Merge the production runtime change to `main`; the workflow builds and publishes the immutable image, uploads the Compose/env files, waits for MySQL, migrates, and starts the stack.
4. Verify service health, the public HTTPS endpoint, queue worker status, scheduler logs, and application logs.
5. For rollback, set `IMAGE_TAG` to a known-good commit SHA and run the documented Compose pull/up sequence. Do not remove volumes.

