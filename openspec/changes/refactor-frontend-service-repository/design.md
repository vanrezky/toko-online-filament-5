## Context

See `proposal.md` for the motivation and Issue #5 for the product-facing scope. The repository currently has 23 Frontend controllers, existing focused services in `app/Services/`, no application repository layer, and selected Filament resources that still access Eloquent directly. Checkout and order paths combine validation, queries, locks, transactions, inventory, voucher, installment, payment, jobs, and response formatting.

The refactor must start from the current `main` branch, preserve the existing route and response contracts, and leave the unrelated untracked `bun.lock` untouched. Laravel Sail is the required runtime for backend validation.

## Goals / Non-Goals

**Goals:**

- Establish a small, predictable `Controller → Service → Repository → Eloquent` boundary for Frontend use cases.
- Make touched PHP code strongly typed with `declare(strict_types=1)` and explicit public contracts.
- Keep business transactions and orchestration in services while keeping repositories focused on persistence.
- Reuse existing focused services for payment, inventory, voucher, pricing, installment, cache, and integrations.
- Make services transport-independent so a later Filament or API adapter can reuse them.
- Migrate in slices so each slice can be tested and reviewed independently.

**Non-Goals:**

- No full DDD layer, aggregate/value-object rewrite, module framework, or generic CRUD abstraction.
- No new repository or service interfaces and no dependency injection bindings for concrete classes.
- No schema, route, permission, API contract, UI, payment rule, or inventory rule changes.
- No Filament Resource/Page rewrite in this phase.
- No new static-analysis dependency.

## Decisions

### Concrete repositories without interfaces

Repositories live under `app/Repositories/` and are named for the persistence responsibility they own. They expose domain-specific methods such as loading an active customer cart, finding an owned transaction, or retrieving published content. They return typed models, collections, nullable models, or scalars. Generic `getAll`, `findById`, `save`, and `delete` wrappers are not introduced.

An interface is deferred because each repository has one implementation and the project has one developer. Existing interfaces that model real replaceable integrations, such as the payment gateway contract, remain unchanged.

### Services own use cases and transactions

Services remain in `app/Services/` and expose operations named for customer or application actions. A service receives validated values and models, coordinates existing focused services and jobs, and owns `DB::transaction()` when a use case spans multiple writes. Repositories do not decide HTTP behavior and do not own a transaction that crosses use-case operations.

Existing focused services are extended only when the behavior already belongs to them. A new orchestration service is added only where a controller currently combines multiple responsibilities.

### Controllers remain transport adapters

Controllers keep route model binding, authentication/authorization checks, request validation, and response formatting. They inject services through constructors and do not issue Eloquent queries, perform business calculations, start transactions, or call external integrations directly.

Validation may remain inline while behavior is preserved; a Form Request is extracted only when the existing rules are complex enough to reduce controller responsibility without changing the contract.

### Strong typing is enforced at touched boundaries

Every new or modified PHP file starts with `declare(strict_types=1)`. Constructor properties, parameters, return types, nullable values, enums, models, and collections are explicit. Simple associative arrays use documented array-shape PHPDoc; a small typed data object is used only when a payload crosses a service boundary and its shape cannot remain clear as an array. `mixed` is not used for primary service or repository contracts.

### Migration slices

The implementation proceeds in this order:

1. Establish repository/service conventions with low-risk catalog and content reads.
2. Migrate account and engagement flows, including the web/API contact adapter.
3. Migrate cart, checkout, order, and installment flows while preserving locks, transaction ordering, and payment side effects.
4. Migrate remaining Frontend authentication and review paths where direct persistence or business logic remains.

Each slice replaces the controller call site immediately, keeps the old behavior as the oracle, and adds or updates the smallest feature/service tests needed for its decisions.

## Risks / Trade-offs

- **Checkout regression or lost atomicity** → Keep transaction ownership in the service, preserve `lockForUpdate()` and operation order, and run focused checkout/order/inventory tests before the broader suite.
- **Repository becomes a second service** → Review each method against the rule that repositories only query or persist; move decisions back to the service.
- **Typed arrays expose inconsistent existing shapes** → Document the current shape first and introduce a typed data object only for a genuinely shared complex payload.
- **Concrete classes are harder to mock than interfaces** → Prefer feature tests and real repository behavior; mock a concrete collaborator only at a meaningful external boundary. Add an interface later if a second implementation or stable replacement seam appears.
- **Large refactor becomes difficult to review** → Keep one logical slice per commit/PR and do not mix Filament, UI, schema, or behavior changes.

## Migration Plan

1. Capture the current route, feature-test, and response behavior for the affected slice.
2. Add the concrete repository and strongly typed service operation.
3. Move query/persistence code from the controller into the repository and move orchestration/transactions into the service.
4. Reduce the controller to validation, authorization, service invocation, and response formatting.
5. Run focused Sail tests, Pint, and route/contract checks after each slice.
6. Run the full Sail test suite and OpenSpec strict validation before review.
7. Roll back a slice by reverting its commit; no database rollback is required because this change has no schema migration.
