# Development Workflow

This repository uses an issue-first development workflow with optional OpenSpec planning based on change complexity and risk.

The agent must determine the appropriate workflow automatically before editing application code.

## Ponytail

- For every coding task, use the installed `ponytail` skill automatically at `full` intensity.
- Apply YAGNI: inspect the existing code first, reuse existing patterns, prefer standard-library/native/dependency solutions, and implement the smallest correct change.
- Continue to follow all repository rules; do not reduce validation, security, accessibility, error handling, or explicit requirements.
- Do not use Ponytail for non-coding tasks.

## Mandatory Laravel Sail Policy

All Laravel backend commands MUST run through Laravel Sail.

For backend tests, the canonical command is:

```bash
./vendor/bin/sail artisan test
```

For a focused backend test, use:

```bash
./vendor/bin/sail artisan test --filter TestName
```

Do NOT run backend tests through the host PHP installation. The following commands are forbidden:

- `php artisan test`
- `./vendor/bin/phpunit`
- `./vendor/bin/phpunit --filter TestName`
- `./vendor/bin/sail test`

The same Sail requirement applies to PHP, Artisan, Composer, and Pint commands. Use `./vendor/bin/sail php ...`, `./vendor/bin/sail artisan ...`, `./vendor/bin/sail composer ...`, and `./vendor/bin/sail pint ...`.

Before running backend tests or other service-dependent validation, inspect the existing environment with:

```bash
docker compose ps
```

If Sail or the `laravel.test` container is unavailable, diagnose and report the environment issue. Do not fall back to host PHP or start another Laravel/Docker environment.

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
- Backend tests: `./vendor/bin/sail artisan test`.
- Single backend test: `./vendor/bin/sail artisan test --filter TestName`.
- Frontend iteration verification: browser/HMR at `http://localhost:81` and focused frontend tests (`npm run test` with relevant filters) when applicable. Run `npm run build` only at a logical checkpoint, final handoff/PR, or when explicitly requested; follow the Vite/HMR workflow below.

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

### Vite/HMR workflow

- For ordinary Vue/CSS/UI changes, use the existing Vite dev server and verify the rendered result through HMR at `http://localhost:81`. Do not run `npm run build` after every edit.
- Reserve `npm run build` for a logical batch checkpoint, final handoff/PR validation, or an explicit user request. Build validation does not replace browser verification of the UI.
- Do not automatically stop, restart, or launch another Vite dev server. Preserve the user's running `npm run dev` process.
- If changes do not appear, inspect `public/hot`, the JS/CSS URLs actually loaded by the page, the HMR WebSocket connection, and terminal/browser errors before attempting recovery. Confirm that the page is using the active dev server rather than production assets in `public/build`.
- Laravel uses `public/hot` to select the dev server and otherwise loads built assets. Do not delete or rewrite `public/hot` as routine cleanup or as a workaround for stale UI.
- A successful production build does not by itself require restarting `npm run dev`. Ordinary component/style edits should update through HMR; some configuration or dependency changes may require a restart. Diagnose and explain that need before restarting, and coordinate with the user who owns the running process.
- Report browser/HMR verification, focused tests, and production build results separately. If changes are not visible or browser verification is unavailable, state that limitation rather than claiming the UI is verified.

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.3. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v11 rules ===

# Laravel 11

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel 11 file structure.
- This is perfectly fine and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the Laravel 11 structure unless the user explicitly requests it.

## Laravel 10 Structure

- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

## New Artisan Commands

- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class`
    - `php artisan make:interface`

=== laravel-octane/core rules ===

# Laravel Octane

This application uses Laravel Octane, a long-running PHP server. The application bootstraps once and handles many requests within the same process.

- Never store request-specific state in singletons or static properties, because it can leak across requests.
- Use `config('octane.server')` to detect the active driver (`swoole`, `roadrunner`, or `frankenphp`).
- Prefer scoped bindings (`$this->app->scoped()`) over singletons for per-request services.

When working on Octane-specific features (concurrency, shared tables, memory, driver configuration, testing), invoke `octane-development` for detailed rules.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
