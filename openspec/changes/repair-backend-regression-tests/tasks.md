## 1. Issue and OpenSpec traceability

- [x] 1.1 Create approved Issue #90 with `type:chore`, `priority:P2`, and `status:needs-spec`.
- [x] 1.2 Record the diagnosis and no-silent-production-change decision in proposal and design.

## 2. Align regression fixtures and assertions

- [x] 2.1 Update Contact Message tests to use authorized administrator fixtures.
- [x] 2.2 Update the home smoke test to assert the current successful response.
- [x] 2.3 Make payroll test settings explicit and assert transaction billing Enum values correctly.
- [x] 2.4 Normalize the audit test monetary fixture so description-only updates remain non-audited.
- [x] 2.5 Assert root-relative media paths through the installed Spatie API.
- [x] 2.6 Update public/private store tests with the current registration and Customer schema fields.
- [x] 2.7 Confirmed no application code change is required; focused regressions prove the implementation matches the current contract.

## 3. Verification

- [x] 3.1 Run all repaired test groups through Laravel Sail.
- [x] 3.2 Run formatter/static checks for changed PHP files.
- [x] 3.3 Run `git diff --check`.
- [x] 3.4 Run the complete backend suite through Laravel Sail.
- [x] 3.5 Validate this OpenSpec change with `openspec validate repair-backend-regression-tests --type change --strict`.
- [ ] 3.6 Update Issue #90 status and prepare a linked Pull Request after verification.
