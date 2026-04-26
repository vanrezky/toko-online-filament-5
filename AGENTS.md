# AGENTS.md — Project Instructions for AI

## Tech Stack

- **Backend:** Laravel 11 (PHP 8.2+), MySQL 8.0, Redis
- **Frontend:** Vue 3 (Composition API) + Inertia.js, Tailwind CSS 3, Vite 7
- **Admin:** Filament 3
- **Local Dev:** Laravel Sail (Docker)
- **Node:** v22 (`nvm use 22`)
- **Validation:** VeeValidate + Zod (frontend)
- **UI:** Radix Vue / Reka UI + Flowbite

## Architecture

- Server-side rendered via Inertia (not SPA)
- Backend: Service pattern (`app/Services/`)
- Admin: Filament Resources (`app/Filament/Resources/`)
- Frontend: `resources/js/frontend/` (pages, components, composables, services)
- Models: `app/Models/` (50+ Eloquent models)

## Mandatory Steps Before Generating Code

1. **Read database schema** — Always read `database/schema/mysql-schema.sql` before creating any UI or model-related code
2. **Read existing PRDs** — Check `.docs/` for relevant PRDs before implementing features
3. **Do NOT invent fields** — Only use columns that exist in migrations/schema. If a field is missing, ask first
4. **Follow existing patterns** — Look at neighboring files before creating new ones

## API Documentation Reference

Always read the relevant API documentation BEFORE writing payment/shipping/integration code:

| Service | Documentation | When to Read |
|---------|--------------|--------------|
| **Midtrans Payment** | `.docs/api/midtrans-api.md` | Before any payment gateway, checkout, or webhook code |
| **Dynamic Payment Gateway PRD** | `.docs/gateways-prd.md` | Before modifying `PaymentGatewayService`, gateway implementations, or Filament payment settings |
| **Frontend PRD** | `.docs/prd-fe.md` | Before frontend/UI changes |
| **Voucher PRD** | `.docs/prd-voucher.md` | Before voucher-related changes |
| **Backend Agent Guide** | `.docs/agent-be.md` | Before backend implementation |

## Code Conventions

### PHP / Laravel

- Follow existing Service pattern in `app/Services/`
- Use Laravel conventions (Eloquent ORM, form requests, resource classes)
- Filament resources follow existing structure in `app/Filament/Resources/`
- Settings use Spatie Laravel Settings (`app/Settings/`)
- Use `app/Constants/`, `app/Enums/`, `app/Helpers/` for shared logic

### Vue / Frontend

- Composition API with `<script setup>` syntax
- Pages in `resources/js/frontend/pages/`
- Components in `resources/js/frontend/components/`
- Composables in `resources/js/frontend/composables/`
- Services (API calls) in `resources/js/frontend/services/`
- Use existing UI components (Radix Vue, Flowbite) — don't add new UI libraries

### Database

- Check `database/schema/mysql-schema.sql` for existing tables/columns
- Follow existing migration naming conventions in `database/migrations/`
- Settings migrations go in `database/settings/`

## Key Directories

```
app/
├── Console/           # Artisan commands
├── Constants/         # Shared constants
├── Enums/             # PHP enums
├── Exceptions/        # Custom exceptions
├── Filament/          # Admin panel (Resources, Pages, Widgets)
├── Forms/             # Form classes
├── Http/
│   ├── Controllers/
│   │   ├── Api/       # API controllers (VoucherController)
│   │   └── Frontend/  # Web controllers (Home, Cart, Checkout)
│   ├── Middleware/
│   ├── Requests/      # Form request validation
│   └── Resources/     # API resources
├── Models/            # 50+ Eloquent models
├── Services/          # Business logic
│   ├── Gateways/      # Payment gateway implementations
│   └── ...
├── Settings/          # Spatie settings
└── ...

resources/js/frontend/
├── components/       # Vue components
├── composables/      # Vue composables
├── pages/            # 14 page directories
├── services/         # API service modules
└── main.js
```

## Payment Integration Rules

When working on Midtrans/payment code:

1. **READ** `.docs/api/midtrans-api.md` first for API endpoints, payloads, and status codes
2. **READ** `.docs/gateways-prd.md` for the PaymentGatewayInterface contract and service architecture
3. Use `midtrans/midtrans-php` SDK (already in composer.json)
4. Configure via `config/midtrans.php` + `.env` (never hardcode keys)
5. Webhook handler must verify `signature_key` using SHA512
6. Map Midtrans transaction statuses to project statuses using the status mapping in `.docs/api/midtrans-api.md`
7. Always return `200 OK` from webhook handler after successful processing

## Git & Testing

- Run `./vendor/bin/sail test` or `./vendor/bin/phpunit` for backend tests
- Run `npm run build` to verify frontend compiles
- Never commit `.env` or secrets