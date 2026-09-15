## Why

The Frontend controllers mix HTTP concerns with Eloquent queries, business rules, transactions, integrations, and response formatting. This is most visible in checkout and order flows, where a change can affect payment, inventory, vouchers, installment, and the shared web/API contact flow. Issue #5 establishes a behavior-preserving refactor so one developer can maintain these flows without introducing speculative abstractions.

## What Changes

- Move Frontend use-case orchestration from controllers into strongly typed application services.
- Move reusable and non-trivial Eloquent queries and persistence operations into concrete repositories under `app/Repositories/`.
- Keep controllers responsible for authentication/authorization, request validation/binding, service invocation, and Inertia/JSON/redirect responses.
- Keep transactions, domain decisions, and existing integrations in services, reusing existing focused services where they already own the rule.
- Add `declare(strict_types=1)` and explicit PHP types to touched controllers, services, and repositories.
- Preserve route names, middleware, authorization, validation outcomes, response contracts, database schema, and business behavior.
- Do not add repository/service interfaces, generic base repositories, or new dependencies.

## Capabilities

### New Capabilities

- `frontend-service-repository-boundaries`: Strongly typed service and repository boundaries for Frontend use cases while preserving existing storefront behavior.

### Modified Capabilities

- None. Existing storefront requirements remain unchanged; this change defines an internal application boundary.

## Impact

- Affects `app/Http/Controllers/Frontend/`, `app/Services/`, and new concrete classes under `app/Repositories/`.
- Affects service and feature tests covering account, catalog, cart, checkout, order, payment, voucher, installment, review, contact, newsletter, wishlist, and authentication flows where code moves.
- Filament resources and pages remain unchanged in this phase, but the resulting services are usable by Filament in a later phase.
- No database migration, endpoint, dependency, or UI change is expected.
