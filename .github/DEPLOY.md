# Production Docker deployment

Production runs from `docker-compose.production.yml` with FrankenPHP/Octane. It
does not use host Nginx, host PHP-FPM, or a host cron. The app, queue worker,
scheduler, MySQL, and optional Redis are separate Compose services. The
production image is built once, tagged with the commit SHA, and pulled from
GHCR by the VPS.

Development remains a separate workflow:

```sh
make start
make dev
```

## One-time VPS setup

Prepare a Linux VPS with Docker Engine, the Docker Compose plugin, SSH, and a
deploy user. The deploy user must be able to run Docker without `sudo`:

```sh
sudo adduser deploy
sudo usermod -aG docker deploy
sudo mkdir -p /srv/toko-online
sudo chown -R deploy:deploy /srv/toko-online
```

Install Docker using the official instructions for the VPS distribution, then
reconnect the SSH session after adding the user to the Docker group. Open ports
80 and 443 in the firewall and point the production DNS record to this VPS.
Do not install or enable Nginx or PHP-FPM for this stack; FrankenPHP serves the
HTTP(S) traffic directly.

## GitHub production Environment

Create `Settings → Environments → production`. Add the deployment credentials
as Secrets:

- `SERVER_HOST`: VPS IP address or hostname.
- `SERVER_USER`: normally `deploy`.
- `SERVER_SSH_PORT`: SSH port, normally `22`.
- `SSH_PRIVATE_KEY`: private key matching the deploy user's `authorized_keys`.
- `SERVER_DEPLOY_PATH`: normally `/srv/toko-online`.

Add these required application values as individual Secrets or Variables. The
workflow fails before upload and prints only the missing key name:

- Secrets: `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `MAIL_PASSWORD`.
- Variables: `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, `OCTANE_HOST`, `MAIL_HOST`,
  `MAIL_USERNAME`, and `MAIL_FROM_ADDRESS`.

The remaining keys are also individual Environment Variables/Secrets, not a
multiline file. Use the names in [`.env.production.example`](../.env.production.example)
and keep sensitive values in Secrets, including database/mail/API credentials.
The workflow has an explicit allowlist for the application, mail, R2, AWS,
Pusher, OAuth, health, audit, and runtime keys. It does not read, parse, or
require `PRODUCTION_ENV`.

### OPcache values

Add these as production Environment Variables. They are written into PHP's
runtime INI whenever an app, worker, scheduler, migration, or other Artisan
container starts:

```env
OPCACHE_ENABLE=1
OPCACHE_ENABLE_CLI=1
OPCACHE_MEMORY_CONSUMPTION=128
OPCACHE_INTERNED_STRINGS_BUFFER=16
OPCACHE_MAX_ACCELERATED_FILES=20000
OPCACHE_VALIDATE_TIMESTAMPS=0
OPCACHE_REVALIDATE_FREQ=0
OPCACHE_SAVE_COMMENTS=1
OPCACHE_JIT=off
OPCACHE_JIT_BUFFER_SIZE=0
```

`OPCACHE_VALIDATE_TIMESTAMPS=0` is intentional for immutable production
images. After PHP code changes, deploy a new image or recreate the service;
editing files inside a running container is not a supported update path.

## First deployment

After the Environment values are present, merge the change to `main` or run
the workflow manually. GitHub Actions will:

1. Build Composer production dependencies and Vite assets in the production image.
2. Push both the commit-SHA tag and the convenience `production` tag to GHCR.
3. Render one `.env.production` entry per allowlisted key without logging values.
4. Upload the Compose manifest and environment file with restrictive permissions.
5. Pull the immutable image, wait for MySQL health, and run `migrate --force`.
6. Start the app, worker, scheduler, and enabled optional services.
7. Run Laravel optimization and show the final Compose service status.

For a new database, review and run seeders manually when needed. Never run
`migrate:fresh` on production data.

## Service operation and verification

Run these commands from the deployment directory on the VPS:

```sh
cd /srv/toko-online
docker compose -f docker-compose.production.yml --env-file .env.production ps
docker compose -f docker-compose.production.yml --env-file .env.production logs --tail=100 app worker scheduler
curl -I https://example.com
```

The `worker` service runs `php artisan queue:work`; the `scheduler` service runs
`php artisan schedule:work`. They restart automatically if their process
exits. To restart only background processing:

```sh
docker compose -f docker-compose.production.yml --env-file .env.production restart worker scheduler
```

Redis is disabled by default to preserve the 2 GB VPS budget. To enable it,
set the individual `COMPOSE_PROFILES` value to `redis`, set
`QUEUE_CONNECTION` or `CACHE_DRIVER` to `redis`, and configure `REDIS_HOST=redis`.
The Redis service persists data in `redis-data` and is capped at 64 MB of Redis
data and 96 MB of container memory.

To verify OPcache after a service recreation:

```sh
docker compose -f docker-compose.production.yml --env-file .env.production exec -T app php -i | grep -E '^opcache\.(enable|memory_consumption|validate_timestamps|jit)'
```

## Rollback and data safety

The commit SHA is the immutable deployment unit. Roll back to a known-good
image without changing persistent volumes:

```sh
cd /srv/toko-online
export IMAGE_TAG=<known-good-commit-sha>
docker compose -f docker-compose.production.yml --env-file .env.production pull app worker scheduler
docker compose -f docker-compose.production.yml --env-file .env.production up -d --wait --remove-orphans
```

Keep migrations backward-compatible with the previous image before deploying.
Back up MySQL outside Docker volumes and test restoring the backup. Never run
`docker compose down -v`, remove `mysql-data`, remove `redis-data`, remove
`app-storage` or the FrankenPHP `caddy-data`/`caddy-config` volumes, or prune
volumes on this host during deployment or rollback.
