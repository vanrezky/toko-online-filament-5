# AGENTS.md

## Verified stack and entrypoints
- Backend is Laravel `^10.10` on PHP `^8.2` (`composer.json`), with Filament v3 and Inertia.
- Frontend boot path is `resources/js/frontend.js` -> `resources/js/frontend/main.js` (Vue + Inertia app setup).
- Vite builds exactly these inputs: `resources/css/app.css`, `resources/css/filament/admin/theme.css`, `resources/js/frontend.js` (`vite.config.js`).

## Use these commands (repo defaults)
- Bring up local services with Sail: `make start`.
- Fresh local bootstrap: `make setup` (includes `migrate:fresh`, `db:seed`, `storage:link`, `optimize`).
- Full rebuild from scratch: `make fresh`.
- Backend tests: `./vendor/bin/phpunit` (or `./vendor/bin/sail test`).
- Single backend test: `./vendor/bin/phpunit --filter TestName`.
- Frontend verify: `npm run build`; frontend tests: `npm run test`.

## High-signal gotchas
- `phpunit.xml` pins test DB to `127.0.0.1:3307`, database `toko_online_testing`; tests can fail outside Sail if this DB/port is missing.
- `routes/web.php` has product wildcard `Route::get('{product}', ...)` and it must remain last in the `frontend.` group.
- Payment webhook endpoint is `POST /webhooks/payment/{gateway}` (`routes/web.php`).
- `opencode.json` MCP env also expects DB port `3307` and app URL `http://localhost:81`.

## Before editing domain-heavy features
- Check schema first: `database/schema/mysql-schema.sql` (do not invent fields).
- Read relevant specs before implementing:
  - Frontend flows: `.docs/prd-fe.md`
  - Voucher behavior: `.docs/prd-voucher.md`
  - Payment gateway architecture: `.docs/gateways-prd.md`
  - Midtrans API details: `.docs/api/midtrans-api.md`

## Deployment facts to match when debugging CI/prod-only issues
- Deploy workflow runs on push to `main` (`.github/workflows/deploy.yml`).
- CI runtime is PHP `8.3` + Node `22`, installs (`composer install --no-dev`, `npm ci`), then `npm run build`.
