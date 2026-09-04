## Context

The Sail backend run on 2026-09-05 reported 284 passing tests and 10 failures. The failures are distributed across tests added or affected by existing feature commits. Source inspection and focused reproduction show that the current application contracts are generally intentional; the test suite contains stale assumptions.

## Decisions

### Prefer test corrections when the application contract is already intentional

Tests will be corrected when the failure is caused by an outdated assertion or invalid fixture:

- Contact Message tests will use a superuser or the specific `View:ContactMessage` permission.
- The home smoke test will assert the current `200` response.
- Payroll fixtures will explicitly set `billing_due_month_offset` to `0` when testing a direct June due period, and transaction billing assertions will use `TransactionBillingStatus` values.
- Audit fixtures will use a two-decimal `sale_price` or `null` so a description-only update cannot expose a database rounding difference.
- Media assertions will use `getPathRelativeToRoot()`, while URL/resource assertions remain unchanged.
- Registration fixtures will send `first_name`/`last_name` and create Customers with the non-null schema fields.

### Application changes require a focused production-behavior proof

No application behavior will be changed solely to make a test pass. If a focused test demonstrates that runtime authorization, payroll selection, audit filtering, media storage, or private-store access contradicts the established requirement, the implementation and corresponding regression test will be updated together and documented in this change.

### Keep unrelated configuration cleanup separate

The duplicate `r2` array key observed in `config/filesystems.php` is a separate configuration defect and is not a cause of the reported failures. It remains out of scope for Issue #90.

## Risks and Mitigations

- Test fixtures could accidentally hide a real regression. Mitigate by asserting the relevant current contract directly and rerunning the focused test plus the full Sail suite.
- Billing tests could pass while targeting the wrong period. Mitigate by setting the offset explicitly and retaining coverage for the offset behavior in the existing billing tests.
- Authorization tests could over-grant access. Mitigate by using the established authorization tests as the pattern and preserving unauthorized coverage elsewhere.

## Verification Strategy

1. Run each failed test group through `./vendor/bin/sail artisan test --filter ...`.
2. Review the diff to confirm production code is unchanged unless a focused proof requires it.
3. Run formatter/static checks for changed PHP files and `git diff --check`.
4. Run the complete backend suite through `./vendor/bin/sail artisan test`.
5. Validate this OpenSpec change with strict validation.
