## Purpose

Provide administrators with safe visibility and maintenance controls for cache entries created by the application, without exposing Redis administration capabilities or affecting unrelated runtime data.

## ADDED Requirements

### Requirement: Cache overview exposes safe application cache metadata

The Platform Cache Management page SHALL display the configured application cache store, default store, safe cache prefix when available, relevant cache connection name when available, and an operational status of Connected or Unavailable. The page MUST NOT expose passwords, tokens, URLs containing credentials, or other connection secrets.

#### Scenario: Connected application cache

- **WHEN** an authorized administrator opens Cache Management and the configured application cache store responds to a lightweight check
- **THEN** the page shows Connected and the configured non-secret store, prefix, default store, and connection metadata

#### Scenario: Unavailable application cache

- **WHEN** an authorized administrator opens Cache Management and the configured application cache store cannot be checked
- **THEN** the page shows Unavailable with a safe operational message and does not expose the raw exception or credentials

### Requirement: Cache health reuses existing platform health information when possible

Cache Management SHALL use the existing Platform health result for a matching Redis health check when that result is available and SHALL perform only a lightweight application-cache check when a matching result is unavailable or does not represent the configured application cache store. It MUST NOT introduce a second general health monitoring system.

#### Scenario: Existing health result is available

- **WHEN** System Health has a current Redis result that corresponds to the application cache connection
- **THEN** Cache Management presents that operational result without creating a duplicate health record

#### Scenario: Existing health result is not usable

- **WHEN** no usable matching Redis result exists
- **THEN** Cache Management performs a lightweight check through the application cache abstraction and reports the resulting status

### Requirement: Cache groups have separate safe clear actions

The page SHALL provide a separate authorized clear action for each declared application-managed cache group, including Frontend content, Template, Dashboard, Regional, Voucher, Product statistics, Navigation, and Shipping where those groups are available. Each action MUST invalidate only its selected group, preserve queue, Horizon, session, rate limiter, and unrelated Redis data, and MUST NOT accept arbitrary keys or Redis commands.

#### Scenario: Clear one managed application cache group

- **WHEN** an administrator with cache management permission confirms the clear action for one group
- **THEN** only the selected application cache group is invalidated through the application cache abstraction and the page reports the selected group and outcome

#### Scenario: Optional clear all managed application cache groups

- **WHEN** an administrator with cache management permission confirms an optional clear-all action
- **THEN** all declared application cache groups are invalidated through the application cache abstraction and the page reports the aggregate outcome

#### Scenario: Unrelated runtime data is protected

- **WHEN** managed application cache is cleared while queue, Horizon, session, rate limiter, or unrelated Redis data exists
- **THEN** those unrelated data sets remain available and operational

#### Scenario: Cache clear fails safely

- **WHEN** an application cache group cannot be invalidated
- **THEN** the action reports failure without exposing internals, does not attempt a broad Redis flush, and records the failure for operational diagnosis

### Requirement: Application cache usage is registered for maintenance

Cache entries created by application services that are included in Cache Management SHALL be assigned to a managed cache group so the clear action can invalidate them without key browsing or arbitrary pattern deletion.

#### Scenario: Dynamic application cache entry

- **WHEN** an application service creates a cache entry with a dynamic identifier
- **THEN** the entry belongs to a declared managed group and is invalidated when that group is cleared

#### Scenario: Unmanaged external cache data

- **WHEN** data exists outside the application's declared managed groups
- **THEN** Cache Management does not inspect, display, or delete that data

### Requirement: Cache management is permission protected

Cache Management SHALL require a dedicated view permission to open the page and a dedicated management permission to execute the clear action. Superusers SHALL retain access consistent with other Platform pages.

#### Scenario: Superuser access

- **WHEN** a superuser opens Cache Management or executes the clear action
- **THEN** the request is authorized

#### Scenario: Permissioned administrator access

- **WHEN** an administrator has the view permission but not the management permission
- **THEN** the administrator can view the overview but cannot execute the clear action

#### Scenario: Unauthorized access

- **WHEN** a user lacks the dedicated view permission and is not a superuser
- **THEN** access to the page is forbidden

### Requirement: Maintenance actions are auditable

Every attempted application-cache clear SHALL create an audit record through the existing Platform audit mechanism containing the action outcome and safe cache scope metadata, without storing secrets or cache values.

#### Scenario: Successful clear is audited

- **WHEN** a clear action completes successfully
- **THEN** an audit record identifies the actor, cache-management action, selected group or aggregate scope, outcome, and correlation context

#### Scenario: Failed clear is audited

- **WHEN** a clear action fails
- **THEN** an audit record identifies the actor, safe scope, failure outcome, and correlation context without storing raw exception details or secrets

### Requirement: Broad Redis flush operations are prohibited

Cache Management SHALL NOT execute FLUSHALL, FLUSHDB, arbitrary Redis commands, arbitrary key deletion, or Redis key browsing as part of overview or maintenance behavior.

#### Scenario: Clear action uses safe operations

- **WHEN** the clear action is executed
- **THEN** its operation set contains only declared application-cache invalidation operations and no broad Redis flush or arbitrary command operation
