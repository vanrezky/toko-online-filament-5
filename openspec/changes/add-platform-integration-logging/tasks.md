## 1. Integration module foundation

- [ ] 1.1 Add the `integration_logs` migration, indexes, nullable morph subject, JSON payload columns, execution fields, and payload truncation indicator following current database conventions.
- [ ] 1.2 Add the Platform Integration model, casts, relationships, direction/type/status constants or enums, and factory support needed by tests.
- [ ] 1.3 Add integration logging configuration for payload size, retention days, and extendable sensitive-key policy.
- [ ] 1.4 Implement recursive pre-persistence sanitization, binary/body omission, bounded payload capture, and focused unit coverage.
- [ ] 1.5 Implement correlation context and a best-effort lifecycle logger that records pending, success, and failed executions without leaking secrets or changing business errors.

## 2. Representative provider boundaries

- [ ] 2.1 Instrument `ApicoidOngkirService` with the existing Laravel HTTP Client so outbound courier requests capture sanitized request/response details, HTTP failure status, duration, and rethrown transport exceptions while retaining the current decoded JSON contract.
- [ ] 2.2 Instrument `PaymentWebhookController` so Midtrans requests create a pending inbound log, resolve an optional transaction subject, and finish the same record for accepted, rejected, unavailable, and exception outcomes without changing existing validation, transaction, or response behavior.
- [ ] 2.3 Add focused outbound and inbound feature tests covering success, HTTP/transport failure, webhook failure, correlation/subject behavior, and preserved exceptions/response contracts.

## 3. Platform operations and Filament visibility

- [ ] 3.1 Add configurable `integration-logs:prune` maintenance command and schedule it daily with existing single-server and overlap protections.
- [ ] 3.2 Add the existing-authorization-style `View:IntegrationLogs` Shield permission and tests for allowed and denied access.
- [ ] 3.3 Add a read-only Filament Integration Logs resource under Platform with list badges, filters, bounded indexed search, and sanitized detail sections.
- [ ] 3.4 Add Indonesian admin translations for navigation, columns, filters, status labels, empty states, and detail sections.
- [ ] 3.5 Add Filament resource tests for table visibility, read-only actions, filters/search constraints where practical, and detail rendering of sanitized data.

## 4. Verification and traceability

- [ ] 4.1 Run focused integration module, webhook, and Filament authorization tests; run formatter and static checks relevant to changed PHP files.
- [ ] 4.2 Run the backend test suite and frontend build if the environment supports them; record any unrelated or environment failures precisely.
- [ ] 4.3 Validate the OpenSpec change strictly, review every Issue #49 acceptance criterion against code/tests, and update the Issue workflow status when implementation begins.
