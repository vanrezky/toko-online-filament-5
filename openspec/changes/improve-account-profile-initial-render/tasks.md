## 1. Backend Deferred Props

- [x] 1.1 Refactor `AccountController::__invoke` so profile, addresses, settings, authorization state, and balance visibility remain eager while province options, order activity, and applicable balance history resolve through Inertia Deferred Props.
- [x] 1.2 Preserve existing regional service calls, recent-order query ordering and limit, total-order count, balance gate, resources, and empty values.

## 2. Account Profile Rendering

- [x] 2.1 Update `Account/Profile.vue` to consume deferred props with the existing Inertia Vue `Deferred` component.
- [x] 2.2 Add accessible section-level loading fallbacks without changing profile, address, order, balance, or authorization behavior.

## 3. Automated Regression Coverage

- [x] 3.1 Add or update backend feature coverage for initial deferred metadata and successful deferred prop resolution with balance enabled and disabled.
- [x] 3.2 Add or update frontend coverage for deferred fallbacks, resolved secondary sections, and core profile usability while secondary data is pending.

## 4. Verification and Delivery

- [ ] 4.1 Run the required Sail environment check and focused backend and frontend tests.
- [ ] 4.2 Verify the initial and deferred Inertia responses and the Account Profile in the browser, then record before/after response evidence.
- [x] 4.3 Validate the OpenSpec change, update Issue #122 status to review when the PR is opened, and open the PR targeting `dev`.
