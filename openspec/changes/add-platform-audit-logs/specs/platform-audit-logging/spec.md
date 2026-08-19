## ADDED Requirements

### Requirement: Selective critical-model audit capture
The system SHALL use Spatie Activitylog to persist activities only for `Product`, `Transaction`, and `InstallmentPayment` model lifecycle events selected by the implementation. It MUST NOT enable automatic activity logging for all Eloquent models.

#### Scenario: Product price and stock change
- **WHEN** a Product price and stock are updated
- **THEN** one Product update activity stores the prior and new values for those allow-listed fields

#### Scenario: Unselected model changes
- **WHEN** a model outside the approved audit subjects is updated
- **THEN** no automatic Platform audit activity is created solely from that model update

### Requirement: Safe and useful activity properties
The system SHALL log only allow-listed, meaningful attributes and SHALL capture old/new values for dirty updates. It MUST NOT store passwords, password confirmations, remember tokens, API/access/refresh tokens, secrets, private keys, credentials, card data, request payloads, authorization headers, cookies, or sessions in activity properties.

#### Scenario: Sensitive user data is absent
- **WHEN** an activity is created for a selected subject while an authenticated user exists
- **THEN** the activity properties contain only the selected subject changes and safe context, with no sensitive credential or request data

### Requirement: Actor and system semantics
The system SHALL associate an authenticated actor as the activity causer when one is available. It MUST allow activities with a null causer for queue, console, scheduler, and other system-generated actions without creating a fake System user.

#### Scenario: Authenticated administrator changes a subject
- **WHEN** an authenticated administrator updates a selected subject
- **THEN** the resulting activity causer is that administrator

#### Scenario: System process changes a subject
- **WHEN** a queue job, console command, or scheduler updates a selected subject without an authenticated user
- **THEN** the resulting activity remains valid with a null causer

### Requirement: Safe request metadata
The system SHALL attach only IP address, request method, request URL or path, and a length-bounded user agent when an HTTP request is available. It MUST omit this context when no request exists and MUST NOT serialize request payloads or sensitive transport/session data.

#### Scenario: HTTP-originated update
- **WHEN** a selected subject is updated during an HTTP request
- **THEN** the activity stores only the allow-listed request metadata alongside its audit properties

#### Scenario: Console-originated update
- **WHEN** a selected subject is updated by a console command
- **THEN** the activity has no fabricated request metadata

### Requirement: Explicit business audit activity
The system SHALL provide a Platform Audit service for explicitly recording targeted business actions that need a domain-specific description and structured properties beyond a generic model event. A single business transition MUST NOT create duplicate automatic and explicit activities.

#### Scenario: Targeted business transition
- **WHEN** an implemented transaction or installment-payment business transition requires an explicit audit description
- **THEN** the service records one activity with the actor, subject, and structured before/after context

### Requirement: Configurable non-destructive retention
The system SHALL document a conservative 180-day retention value and SHALL use the installed package's official pruning mechanism. Pruning MUST be disabled unless explicitly enabled by configuration.

#### Scenario: Default deployment configuration
- **WHEN** audit logging is deployed without an explicit pruning enablement setting
- **THEN** audit activities are retained and no prune command is scheduled or executed
