## 1. Midtrans gateway instrumentation

- [x] 1.1 Add outbound integration logging to `MidtransGateway::createPayment()`: start a pending `IntegrationLog` (provider `midtrans`, direction `outbound`, type `api`, method `POST`, snap endpoints, sanitized request payload, `Transaction` subject) before `Snap::getSnapToken`, finish as `success` with the returned token on success, and `fail()` with the exception inside the existing catch block before the failure response is returned.
- [x] 1.2 Add outbound integration logging to `MidtransGateway::getPaymentStatus()`: start a pending log (method `GET`, status endpoint, order id as request body), resolve the `Transaction` subject by `uuid` when available, finish as `success` with the SDK result on success, and `fail()` with the exception inside the existing catch block before the failure response is returned.
- [x] 1.3 Preserve the existing return values and failure responses (`PaymentResponse`/`PaymentStatus`) exactly; verify no change to amounts, expiry, or gateway selection logic.

## 2. Tests

- [x] 2.1 Add feature tests: `createPayment` success records a sanitized outbound `midtrans` log with the token response and `Transaction` subject.
- [x] 2.2 Add feature tests: `createPayment` exception returns the existing failure response and the log is recorded as `failed` with `error_class`/`error_message`.
- [x] 2.3 Add feature tests: `getPaymentStatus` success records the SDK result; exception path returns the existing failure response and records `failed`.
- [x] 2.4 Add tests for subject linking on status check when the transaction resolves and null subject when it does not, plus correlation ID inheritance from the active context.

## 3. Verification and traceability

- [x] 3.1 Run focused integration logging tests and `vendor/bin/pint` on changed files.
- [x] 3.2 Run the backend test suite and record results.
- [ ] 3.3 Validate the OpenSpec change, map every Issue #55 acceptance criterion to code/tests, update the Issue workflow status when implementation begins.
- [ ] 3.4 Sync main specs, archive the OpenSpec change, and open the Pull Request linked to Issue #55.