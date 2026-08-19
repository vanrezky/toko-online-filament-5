## 1. Correlation context foundation

- [x] 1.1 Add `App\Modules\Platform\Support\Correlation` helper backed by Laravel `Context` with `id()`, `get()`, `set()`, `reset()`, `validate()`, `headers()`, and header-name constants.
- [x] 1.2 Add `App\Http\Middleware\CorrelationIdMiddleware` that reads `X-Request-ID`/`X-Correlation-ID`, validates and bounds inbound values, generates a UUID when absent/invalid, stores it via `Context`, and echoes `X-Request-ID` + `X-Correlation-ID` response headers.
- [x] 1.3 Register the middleware in the kernel's global `$middleware` stack.
- [x] 1.4 Register a `CommandStarting` listener in `AppServiceProvider` that assigns an execution-level `correlation_id` when absent, skipping queue-worker commands.
- [x] 1.5 Register a `PendingRequest::withCorrelation()` macro in `AppServiceProvider`.

## 2. Integration and audit integration

- [x] 2.1 Rewire `IntegrationCorrelationContext` to delegate to `Context` (id/get/set), keeping its existing constructor injection and method signatures.
- [x] 2.2 Add `correlation_id` to `AuditLogService::requestMetadata()` from context.
- [x] 2.3 Apply `->withCorrelation()` to the outbound `ApicoidOngkirService` request so the courier call carries `X-Correlation-ID`.

## 3. Filament visibility

- [x] 3.1 Audit Logs resource: add `correlation_id` entries to the metadata infolist section and add a correlation search/filter on `properties->context->correlation_id`.
- [x] 3.2 Integration Logs resource: add a `correlation_id` table column.
- [x] 3.3 Queue Monitor: add recent-failed-jobs data to `QueueMonitorService` (extract `correlation_id` from Horizon payload `illuminate:log:context`) and render it in the Blade page.
- [x] 3.4 Add Indonesian admin translations for the new Audit Logs, Integration Logs, and Queue Monitor strings.

## 4. Tests

- [x] 4.1 HTTP feature tests: request without ID generates one, valid inbound ID preserved, response echoes headers, malformed/oversized ID safely regenerated, log lines include `correlation_id`.
- [x] 4.2 Queue tests: dispatch propagates correlation ID, worker restores it, context cleared after job, retry preserves correlation ID, consecutive jobs do not leak context.
- [x] 4.3 Integration tests: log inherits active correlation ID, explicit value overrides, outbound request carries `X-Correlation-ID`.
- [x] 4.4 Webhook test: inbound webhook receives/generates correlation ID and any dispatched job preserves it.
- [x] 4.5 Audit test: activity metadata contains the active correlation ID.

## 5. Verification and traceability

- [x] 5.1 Run focused correlation, queue, integration, webhook, and audit tests; run `vendor/bin/pint` on changed PHP files.
- [x] 5.2 Run the backend test suite and record results.
- [x] 5.3 Validate the OpenSpec change strictly, map every Issue #53 acceptance criterion to code/tests, and update the Issue workflow status when implementation begins.
- [x] 5.4 Sync main specs, archive the OpenSpec change, and open the Pull Request linked to Issue #53.