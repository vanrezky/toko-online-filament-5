## Why

Issue #90 tracks the red backend regression suite. The current failures are caused by test fixtures and assertions that no longer match established application contracts introduced by recent authorization, billing-cycle, audit, media-library, and private-store changes.

## What Changes

- Align Contact Message authorization tests with the existing superuser-or-permission policy.
- Align the example home response test with the current successful `200` response.
- Make payroll tests explicit about the configured billing month offset and backed Enum values.
- Isolate audit logging tests from database monetary precision artifacts.
- Assert the installed Spatie Media Library relative path API in the product image importer test.
- Update private-store registration and customer-login fixtures to use the current required customer fields.
- Change application code only if focused reproduction proves an application contract is incorrect.

## Capabilities

### New Capabilities

- `backend-regression-test-alignment`: Backend tests accurately exercise the current application contracts and provide reliable regression signals.

### Modified Capabilities

- None. This change does not intentionally alter product behavior.

## Impact

- Updates focused tests under `tests/Feature`.
- May update a production file only if a focused regression demonstrates incorrect runtime behavior.
- Does not change database schema, routes, permissions, billing rules, audit policy, storage behavior, or frontend behavior by assumption.
