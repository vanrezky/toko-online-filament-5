# Development Workflow

This repository uses an issue-first development workflow with optional OpenSpec planning based on change complexity and risk.

The agent must determine the appropriate workflow automatically before editing application code.

## Core Principles

- One logical business outcome should map to:
    - one GitHub Issue;
    - one branch;
    - one OpenSpec change when required;
    - one Pull Request.
- Do not start coding immediately from an ambiguous request.
- Product requirements belong in GitHub Issues.
- Technical requirements, design decisions, and implementation tasks belong in OpenSpec.
- Implementation must be traceable from Issue to specification, code, tests, and Pull Request.
- Risk and business impact take priority over estimated effort or file count.

## Development Request Intake

For every new development request:

1. Read the request carefully.
2. Inspect the relevant code and repository conventions.
3. Check whether a related GitHub Issue already exists.
4. Classify the change as `TRIVIAL`, `STANDARD`, or `COMPLEX`.
5. For a new Issue, determine:
    - Issue type;
    - Issue priority;
    - applicable labels.
6. Select the corresponding development workflow.
7. Do not edit application code before completing the classification.

The agent may classify internally. It does not need to produce a long explanation unless the classification affects the workflow or requires user approval.

## Change Classification

### TRIVIAL

Do not use OpenSpec when all of the following are true:

- The requested behavior is clear and unambiguous.
- The implementation is isolated and straightforward.
- It does not introduce or change business rules.
- It affects at most one module or application layer.
- It does not change:
    - authentication;
    - authorization;
    - payments;
    - security-sensitive behavior;
    - database schema;
    - public API contracts;
    - queues, events, or webhooks;
    - external integrations.
- Regression risk is low.
- The change can be safely implemented and verified in one short session.

Typical examples:

- Typo or copy correction
- Styling-only adjustment
- Small spacing or layout correction
- Obvious null or defensive check
- Local variable rename
- Small error-message correction
- Patch-level dependency update
- Straightforward isolated bug fix

Workflow:

1. Inspect the relevant code.
2. Implement the smallest safe change.
3. Add or update a regression test when appropriate.
4. Run relevant validation.
5. Create a branch and Pull Request when required by repository policy.
6. Report changed files and validation results.

A GitHub Issue is optional for TRIVIAL changes unless repository policy requires one.

### STANDARD

Use lightweight OpenSpec when one or more of the following apply:

- The change modifies business behavior.
- It has multiple acceptance criteria.
- It affects more than one application layer.
- It coordinates backend and frontend behavior.
- It adds or changes validation rules.
- It adds or changes permissions.
- It changes API behavior or status transitions.
- Regression risk is meaningful.
- The implementation may span more than one coding session.
- The requirements are mostly clear and do not require major architectural decisions.

A lightweight OpenSpec change should contain:

- `proposal.md`
- specifications
- `tasks.md`

Create `design.md` only when a meaningful technical decision must be documented.

Workflow:

1. Find an existing GitHub Issue.
2. If none exists, generate a GitHub Issue draft.
3. Determine Issue type and priority automatically.
4. Present the draft for approval.
5. Create the GitHub Issue after approval.
6. Create a linked OpenSpec change.
7. Review the proposal, specifications, and tasks for ambiguity.
8. Implement the approved change.
9. Add automated tests for acceptance criteria.
10. Verify implementation against the Issue and OpenSpec.
11. Create a linked Pull Request.

### COMPLEX

Use full OpenSpec when one or more of the following apply:

- Authentication changes
- Authorization or role changes
- Payment or financial behavior
- Database schema or data migration
- External API integration
- Queue, event, webhook, or distributed workflow
- Security-sensitive behavior
- Architectural refactoring
- Cross-module or cross-repository change
- High regression or production risk
- Important requirement ambiguity
- Multiple implementation approaches with meaningful trade-offs
- Data integrity or idempotency concerns

Full OpenSpec workflow:

1. `explore` when requirements or causes are unclear.
2. Create the GitHub Issue.
3. Create the OpenSpec proposal.
4. Create specifications and scenarios.
5. Create `design.md`.
6. Create implementation tasks.
7. Review and approve the artifacts.
8. Apply the change.
9. Run automated validation.
10. Verify Issue, specification, design, code, and tests.
11. Sync specifications.
12. Archive the OpenSpec change.
13. Create or finalize the Pull Request.

Do not begin implementation while critical requirements, security concerns, or architectural decisions remain unresolved.

## Automatic Decision Rules

Always use the highest applicable classification.

Examples:

- A two-line payment change is still `COMPLEX`.
- A small permission change is still `COMPLEX`.
- A large mechanical rename may remain `TRIVIAL` when behavior does not change.
- A simple-looking validation rule may be `STANDARD` when it changes business behavior.
- Estimated duration alone must not determine classification.
- Number of changed files alone must not determine classification.

Classification priority:

1. Security and data integrity
2. Business and production impact
3. Requirement ambiguity
4. Architectural impact
5. Number of layers or modules
6. Estimated implementation size

## Confidence and Escalation

When classification confidence is high:

- Proceed using the selected workflow.

When classification confidence is medium:

- Prefer `STANDARD` with lightweight OpenSpec.
- Briefly state the selected workflow before proceeding.

When classification confidence is low because the requirement is ambiguous:

- Do not guess.
- Ask one focused clarification question.
- Do not write application code yet.

## GitHub Issue-First Workflow

For `STANDARD` and `COMPLEX` changes, the GitHub Issue is the source of product requirements.

When no related Issue exists:

1. Search existing open and closed Issues to prevent duplicates.
2. Generate a draft Issue from the user's request.
3. Automatically classify the Issue type and priority.
4. Present the complete draft for user approval.
5. Do not create the actual Issue until explicitly approved.
6. Do not begin implementation before the Issue exists and is approved.

The Issue draft must contain:

- Title
- Issue Type
- Priority
- Classification Reason
- User Story or Problem Statement
- Context
- Acceptance Criteria
- Validation
- Expected Result
- Out of Scope
- Definition of Done

Do not add product requirements that were not requested.

## Issue Type Classification

The agent must automatically classify every Issue using one primary type.

Available types:

- `feature`
- `bugfix`
- `refactor`
- `chore`

### Feature

Use `feature` when the request introduces a new user-visible or business-facing capability.

Examples:

- Add user registration
- Add queue monitoring
- Add product filtering
- Add a new report
- Add a new API capability

Recommended label:

```text
type:feature
```

Recommended branch prefix:

```text
feat/
```

### Bugfix

Use `bugfix` when existing behavior does not match its intended or documented behavior.

Examples:

- Booking displays the wrong timezone
- Image gallery sometimes fails to appear
- Validation accepts invalid input
- A button triggers the wrong action
- An existing API returns an incorrect result

Recommended label:

```text
type:bugfix
```

Recommended branch prefix:

```text
fix/
```

Do not classify missing functionality as a bug unless the functionality was already required, documented, or previously working.

### Refactor

Use `refactor` when the primary purpose is improving internal code structure without intentionally changing product behavior.

Examples:

- Extracting a service from a controller
- Reorganizing duplicated business logic
- Replacing an internal implementation
- Improving module boundaries
- Simplifying an existing architecture

Recommended label:

```text
type:refactor
```

Recommended branch prefix:

```text
refactor/
```

If a refactor also introduces substantial new behavior, classify it as `feature`.

If a refactor primarily fixes incorrect behavior, classify it as `bugfix`.

### Chore

Use `chore` for maintenance work that does not primarily change business behavior.

Examples:

- Dependency updates
- CI configuration
- Build configuration
- Development tooling
- Documentation maintenance
- Formatter or linter setup

Recommended label:

```text
type:chore
```

Recommended branch prefix:

```text
chore/
```

## Issue Priority Classification

The agent must assign exactly one priority based on urgency, impact, risk, and number of affected users.

Available priorities:

- `P0 — Critical`
- `P1 — High`
- `P2 — Medium`
- `P3 — Low`

Use the highest applicable priority.

### P0 — Critical

Assign `P0` when the issue requires immediate action because it causes or may cause:

- Production outage
- Severe security vulnerability
- Active data loss or corruption
- Incorrect financial transactions
- Critical authentication failure
- A core business flow being unusable for most users
- A production incident with no acceptable workaround

Recommended label:

```text
priority:P0
```

Expected handling:

- Stop lower-priority work when appropriate.
- Investigate immediately.
- Use full OpenSpec when planning will reduce risk, but do not introduce unnecessary delay during an active incident.
- Clearly document mitigation, permanent correction, and validation.

### P1 — High

Assign `P1` when the issue has major impact but is not a complete critical incident.

Examples:

- A core feature is broken for a significant group of users
- A serious authorization or permission issue
- Repeated job or webhook failures
- A high-risk regression
- A deadline-critical business feature
- A problem with a limited temporary workaround
- A significant performance degradation

Recommended label:

```text
priority:P1
```

### P2 — Medium

Assign `P2` for normal planned work with meaningful value or impact.

Examples:

- Normal product feature
- Non-critical bug with a reasonable workaround
- Business validation improvement
- Admin workflow improvement
- Moderate performance improvement
- Technical improvement supporting near-term development

This is the default priority when there is no evidence that the Issue should be urgent or low priority.

Recommended label:

```text
priority:P2
```

### P3 — Low

Assign `P3` when the change has low urgency or limited impact.

Examples:

- Cosmetic issue
- Minor usability improvement
- Typo
- Small internal cleanup
- Optional enhancement
- Low-impact technical debt
- Documentation correction

Recommended label:

```text
priority:P3
```

## Priority Decision Factors

Evaluate priority in this order:

1. Security and data integrity
2. Production availability
3. Financial or transactional correctness
4. Number and importance of affected users
5. Availability of a workaround
6. Business deadline
7. Regression risk
8. User experience impact
9. Implementation effort

Implementation effort must not determine business priority.

Examples:

- A one-line security correction may be `P0` or `P1`.
- A large optional feature may remain `P3`.
- A difficult implementation is not automatically high priority.
- A bug is not automatically higher priority than a feature.
- A feature required for a contractual deadline may be `P1`.

When impact information is missing:

- Do not invent production impact.
- Default to `P2` for meaningful product work.
- Default to `P3` for clearly cosmetic or maintenance work.
- Ask one focused clarification question only when the missing information could reasonably change the classification to `P0` or `P1`.

## Classification Reason

Every generated Issue draft must include a concise classification reason.

Example:

```markdown
## Classification

- **Type:** `bugfix`
- **Priority:** `P1 — High`
- **Reason:** The existing booking flow displays an incorrect date because of timezone conversion. It affects a core transaction flow and may cause users to book the wrong schedule, although the service remains available.
```

The reason must be based on known facts from:

- the user's request;
- repository behavior;
- existing requirements;
- logs, tests, or code inspected by the agent.

Do not claim user impact, production severity, or urgency without evidence.

## Issue Labels

When creating an Issue, apply:

- exactly one type label;
- exactly one priority label;
- exactly one workflow status label.

Recommended labels:

```text
type:feature
type:bugfix
type:refactor
type:chore

priority:P0
priority:P1
priority:P2
priority:P3

status:needs-spec
status:ready
status:in-progress
status:review
status:blocked
```

Default status for a newly approved `STANDARD` or `COMPLEX` Issue:

```text
status:needs-spec
```

After OpenSpec artifacts are complete and approved:

```text
status:ready
```

When implementation starts:

```text
status:in-progress
```

When a Pull Request is opened:

```text
status:review
```

Before creating an Issue, verify that its required type, priority, and workflow status labels exist. If any required label is missing, create it first using the exact recommended taxonomy, then apply it to the Issue. Do not substitute a closest existing label or silently use a different taxonomy.

## GitHub Issue Structure

Use this structure:

```markdown
## Classification

- **Type:** `feature | bugfix | refactor | chore`
- **Priority:** `P0 | P1 | P2 | P3`
- **Reason:** Concise evidence-based explanation

## User Story

As a [type of user],
I want [capability],
so that [business value].

For bugs where a user story is unnatural, use:

## Problem Statement

Describe the current incorrect behavior and its impact.

## Context

Describe the current condition, known evidence, and reason for the change.

## Acceptance Criteria

### Input or Behavior

- [ ] Observable user or system behavior

### Validation

- [ ] Validation and failure behavior

### Expected Result

- [ ] Successful final state

## Out of Scope

- Explicitly excluded behavior

## Definition of Done

- [ ] Acceptance criteria are implemented
- [ ] Relevant automated tests exist
- [ ] Regression tests exist for bug fixes
- [ ] Formatter and static analysis pass
- [ ] Test suite passes
- [ ] Build succeeds
- [ ] Documentation is updated when required
```

## Classification Review

Before creating the actual GitHub Issue, verify:

- The selected type represents the primary purpose of the work.
- The priority is based on impact and urgency, not implementation difficulty.
- Acceptance criteria are observable and testable.
- Bug reports distinguish expected behavior from actual behavior.
- Feature Issues explain the intended user or business value.
- Out-of-scope behavior is explicit.
- No duplicate Issue already exists.

The user may correct the classification before approving the Issue.

After approval, create the Issue with the approved:

- title;
- body;
- type label;
- priority label;
- initial status label.

## OpenSpec Integration

For every OpenSpec change:

- Link the GitHub Issue number in `proposal.md`.
- Preserve the Issue's acceptance criteria.
- Translate acceptance criteria into concrete specification scenarios.
- Put technical decisions in `design.md`.
- Put engineering implementation steps in `tasks.md`.
- Do not place engineering task details in the GitHub Issue.
- Do not add new product scope without updating and approving the Issue.

Recommended mapping:

```text
GitHub Issue
    ↓
OpenSpec proposal
    ↓
OpenSpec requirements and scenarios
    ↓
OpenSpec design
    ↓
OpenSpec tasks
    ↓
Code
    ↓
Automated tests
    ↓
Pull Request
```

## Branch Naming

Use the GitHub Issue number and its classified type.

Mapping:

```text
feature  → feat/
bugfix   → fix/
refactor → refactor/
chore    → chore/
```

Examples:

```text
feat/5-user-registration
fix/18-booking-timezone
refactor/27-payment-service
chore/31-update-ci-workflow
```

The branch prefix must follow the approved GitHub Issue classification.

Use one logical outcome per branch.

## Pull Request Integration

Every `STANDARD` or `COMPLEX` Pull Request must:

- Link the GitHub Issue.
- Reference the OpenSpec change.
- Summarize the implementation.
- List validation performed.
- Include testing instructions.
- Identify risks or migration impact.
- Use `Closes #<issue-number>` when the PR should close the Issue after merge.

Recommended Pull Request structure:

```markdown
## Summary

Briefly describe the implemented outcome.

Closes #<issue-number>

## OpenSpec

`openspec/changes/<change-name>/`

## Acceptance Criteria

- [x] Criterion implemented and verified

## Implementation

- Important implementation decisions

## Validation

- [x] Formatter
- [x] Static analysis
- [x] Automated tests
- [x] Build
- [x] OpenSpec verification

## Risks

- Relevant production, data, security, or regression risks

## Testing Instructions

1. Step-by-step manual verification
```

## Requirement Changes During Pull Request Review

When review feedback changes only implementation details:

- Update code and tests.
- Keep the existing product requirement unchanged.

When review feedback changes product behavior:

1. Update the GitHub Issue.
2. Update OpenSpec requirements and scenarios.
3. Update tasks and design when relevant.
4. Implement the approved change.
5. Update tests.

When review feedback changes architecture:

1. Update `design.md`.
2. Update affected tasks.
3. Implement the approved design.
4. Verify coherence between design and code.

Never allow code, Issue, and OpenSpec to silently drift apart.

## OpenSpec Verification

Before finalizing a `STANDARD` or `COMPLEX` Pull Request, verify traceability across:

- GitHub Issue acceptance criteria
- OpenSpec requirements and scenarios
- OpenSpec design
- OpenSpec tasks
- Source code
- Automated tests

Classify each criterion as:

- passed;
- partial;
- missing;
- conflicting.

Critical missing or conflicting requirements must be resolved before merge.

## OpenSpec Archive Timing

For completed changes:

1. Finish implementation.
2. Run automated tests and build.
3. Open or update the Draft Pull Request.
4. Address review feedback.
5. Run OpenSpec verification.
6. Sync canonical specifications.
7. Archive the OpenSpec change.
8. Push the final documentation commit.
9. Mark the Pull Request ready.
10. Merge after CI and review pass.

## Parallel Development

Multiple changes may run in parallel only when they are sufficiently independent.

Use:

- one Git worktree per change;
- one branch per change;
- one OpenSpec change per branch;
- one coding-agent session per worktree;
- one Pull Request per logical outcome.

Avoid parallel work when changes modify the same core files, schema, service, or business flow.

Recommended maximum:

- two active implementation changes;
- one review or testing change.

## User Overrides

The user may explicitly override the automatic workflow:

- `no openspec` means proceed without OpenSpec unless the change is security-critical, destructive, or high-risk.
- `use openspec` means create an OpenSpec change.
- `full openspec` means use the complete workflow.
- `issue only` means create or draft the GitHub Issue without implementation.
- `implement issue #<number>` means use the existing Issue as the product source and select the appropriate OpenSpec workflow automatically.

When an override creates meaningful risk, warn briefly before proceeding.

## Safety Rules

- Never create duplicate GitHub Issues without checking existing Issues.
- Never start implementation from an unapproved Issue draft.
- Never mark an OpenSpec task complete before its validation succeeds.
- Never check an acceptance criterion merely because code exists.
- Never merge unrelated changes into the same branch or Pull Request.
- Never silently expand scope.
- Never archive an OpenSpec change before implementation and verification are complete, except when explicitly abandoning the change.

## Verified stack and entrypoints

- Backend is Laravel `^10.10` on PHP `^8.2` (`composer.json`), with Filament v3 and Inertia.
- Frontend boot path is `resources/js/frontend.js` -> `resources/js/frontend/main.js` (Vue + Inertia app setup).
- Vite builds exactly these inputs: `resources/css/app.css`, `resources/css/filament/admin/theme.css`, `resources/js/frontend.js` (`vite.config.js`).

## Use these commands (repo defaults)

- Bring up local services with Sail: `make start`.
- Fresh local bootstrap: `make setup` (includes `migrate:fresh`, `db:seed`, `storage:link`, `optimize`).
- Full rebuild from scratch: `make fresh`.
- Backend tests: `./vendor/bin/phpunit` (or `./vendor/bin/sail test`).
- Single backend test: `./vendor/bin/phpunit --filter TestName`.
- Frontend verify: `npm run build`; frontend tests: `npm run test`.

## High-signal gotchas

- `phpunit.xml` pins test DB to `127.0.0.1:3307`, database `toko_online_testing`; tests can fail outside Sail if this DB/port is missing.
- `routes/web.php` has product wildcard `Route::get('{product}', ...)` and it must remain last in the `frontend.` group.
- Payment webhook endpoint is `POST /webhooks/payment/{gateway}` (`routes/web.php`).
- `opencode.json` MCP env also expects DB port `3307` and app URL `http://localhost:81`.

## Before editing domain-heavy features

- Check schema first: `database/schema/mysql-schema.sql` (do not invent fields).
- Read relevant specs before implementing:
    - Frontend flows: `.docs/prd-fe.md`
    - Voucher behavior: `.docs/prd-voucher.md`
    - Payment gateway architecture: `.docs/gateways-prd.md`

## Deployment facts to match when debugging CI/prod-only issues

- Deploy workflow runs on push to `main` (`.github/workflows/deploy.yml`).
- CI runtime is PHP `8.3` + Node `22`, installs (`composer install --no-dev`, `npm ci`), then `npm run build`.

## Development Environment

This project uses Laravel Sail.

The application is already running using Docker / Laravel Sail.

Application URL:

http://localhost:81

Do NOT:

- run `php artisan serve`
- start another Laravel server
- start another Docker environment
- change application ports
- assume another localhost port

Before starting any server, check:

docker compose ps

For frontend verification, always use:

http://localhost:81

The Vite dev server is already managed by the existing development environment.

If the application cannot be reached, diagnose the existing Sail environment.
Do not start a replacement development server.

## Playwright

Playwright Chromium is already installed on the host machine.

Do NOT install:

- Chromium
- Chrome
- Playwright browsers
- browser dependencies

The Playwright MCP is configured to use Chromium.

If Playwright cannot launch a browser, report the error and inspect the
existing MCP/browser configuration instead of installing another browser.


<!-- headroom:rtk-instructions -->
# RTK (Rust Token Killer) - Token-Optimized Commands

When running shell commands, **always prefix with `rtk`**. This reduces context
usage by 60-90% with zero behavior change. If rtk has no filter for a command,
it passes through unchanged — so it is always safe to use.

## Key Commands
```bash
# Git (59-80% savings)
rtk git status          rtk git diff            rtk git log

# Files & Search (60-75% savings)
rtk ls <path>           rtk read <file>         rtk grep <pattern>
rtk find <pattern>      rtk diff <file>

# Test (90-99% savings) — shows failures only
rtk pytest tests/       rtk cargo test          rtk test <cmd>

# Build & Lint (80-90% savings) — shows errors only
rtk tsc                 rtk lint                rtk cargo build
rtk prettier --check    rtk mypy                rtk ruff check

# Analysis (70-90% savings)
rtk err <cmd>           rtk log <file>          rtk json <file>
rtk summary <cmd>       rtk deps                rtk env

# GitHub (26-87% savings)
rtk gh pr view <n>      rtk gh run list         rtk gh issue list

# Infrastructure (85% savings)
rtk docker ps           rtk kubectl get         rtk docker logs <c>

# Package managers (70-90% savings)
rtk pip list            rtk pnpm install        rtk npm run <script>
```

## Rules
- In command chains, prefix each segment: `rtk git add . && rtk git commit -m "msg"`
- For debugging, use raw command without rtk prefix
- `rtk proxy <cmd>` runs command without filtering but tracks usage
<!-- /headroom:rtk-instructions -->
