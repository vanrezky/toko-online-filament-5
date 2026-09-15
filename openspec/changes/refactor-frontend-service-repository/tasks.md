## 1. Baseline and conventions

- [x] 1.1 Record the current Frontend route, authorization, validation, response, transaction, and side-effect contracts for the migration slices.
- [x] 1.2 Establish the concrete repository and strongly typed service conventions without adding interfaces, generic base classes, or dependencies.

## 2. Catalog and content

- [x] 2.1 Move Home, Product, Blog, Page, FAQ, and Flashsale persistence queries behind concrete repositories and typed services.
- [x] 2.2 Reduce the catalog and content controllers to validation/binding, service invocation, and Inertia responses while preserving eager loading, pagination, caching, and resource output.
- [x] 2.3 Add or update focused feature/service tests for catalog and content behavior.

## 3. Account and engagement

- [x] 3.1 Move Account profile/address, Contact, Newsletter, Wishlist, Voucher, and Product Review use cases into typed services and concrete repositories where persistence is non-trivial.
- [x] 3.2 Preserve customer ownership checks, validation, web/API Contact representations, queued newsletter behavior, voucher cookies, and cache invalidation.
- [x] 3.3 Reduce the affected controllers and add focused authorization, validation, and behavior tests.

## 4. Commerce flows

- [x] 4.1 Move Cart reads and mutations into typed services and concrete repositories while preserving inventory locks, pricing, recommendations, and JSON/redirect responses.
- [x] 4.2 Move Checkout orchestration into a typed service and repositories while preserving transaction boundaries, row locks, voucher validation, inventory reservation, installment creation, balance payment, payment initiation, and notification dispatch.
- [x] 4.3 Move Order and Installment reads, payment initiation, cancellation, and schedule queries into typed services and repositories while preserving ownership and status guards.
- [x] 4.4 Add or update focused cart, checkout, order, installment, payment, voucher, and inventory regression tests.

## 5. Authentication and remaining Frontend adapters

- [x] 5.1 Move direct persistence and use-case logic from Frontend authentication controllers into typed services/repositories without changing throttling, password reset, social login, or session behavior.
- [x] 5.2 Reduce the remaining Frontend controllers to transport responsibilities and preserve route model binding and response contracts.
- [x] 5.3 Add or update focused authentication and review regression tests.

## 6. Verification and handoff

- [x] 6.1 Audit touched PHP files for `declare(strict_types=1)`, explicit public types, documented collection/array shapes, and absence of primary `mixed` contracts.
- [x] 6.2 Run focused tests, full `./vendor/bin/sail artisan test`, and `./vendor/bin/sail pint --test` when Sail is available.
- [x] 6.3 Run `openspec validate refactor-frontend-service-repository --type change --strict` and verify Issue #5, specs, design, tasks, code, and tests remain aligned.
- [x] 6.4 Update the architecture documentation if the final service/repository boundaries differ from the design.

> Validation note: dirty-scope Pint passes for the refactor. Full-repository Pint still reports the pre-existing parse error in `app/Jobs/GetProvinceJob.php` and unrelated baseline formatting findings.
