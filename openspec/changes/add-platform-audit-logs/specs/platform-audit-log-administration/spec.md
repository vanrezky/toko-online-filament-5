## ADDED Requirements

### Requirement: Authorized read-only Audit Logs access
The system SHALL provide a native Filament Audit Logs resource under the existing Platform navigation group. It SHALL allow access only to a superuser or a user with `View:AuditLogs`, and it MUST expose no create, edit, delete, bulk-delete, or activity mutation action.

#### Scenario: Superuser opens Audit Logs
- **WHEN** a superuser opens Platform Audit Logs
- **THEN** the read-only Audit Logs listing is displayed

#### Scenario: Permission-granted technical user opens Audit Logs
- **WHEN** a non-superuser with `View:AuditLogs` opens Platform Audit Logs
- **THEN** the read-only Audit Logs listing is displayed

#### Scenario: Unauthorized user requests Audit Logs
- **WHEN** a user without superuser status or `View:AuditLogs` requests Audit Logs
- **THEN** the application returns forbidden access

### Requirement: Useful audit listing and filtering
The Audit Logs list SHALL display timestamp, actor, action/event, subject type, subject, and description. It SHALL provide actor, event/action, subject type, and date-range filters, and SHALL provide constrained search for description, subject ID, and actor name/email where supported by the stored data.

#### Scenario: Technical user filters by subject and date
- **WHEN** an authorized user filters Audit Logs by subject type and date range
- **THEN** the list contains only matching paginated activities

#### Scenario: Activity has no causer
- **WHEN** an activity has a null causer
- **THEN** its Actor column displays System

### Requirement: Readable audit detail and field-level diff
The Audit Logs resource SHALL provide a detail view showing actor, action, subject, timestamp, safe request metadata, and an ordered old/new field-level diff. It MUST present an understandable empty state when properties or changed values are absent and MUST NOT use raw JSON as the primary diff presentation.

#### Scenario: Updated activity detail
- **WHEN** an authorized user views an activity that changed price and stock
- **THEN** the detail displays each field with its previous and new values in a readable diff

#### Scenario: Activity without changed properties
- **WHEN** an authorized user views an activity that has no old/new properties
- **THEN** the detail indicates that no field-level changes are available without rendering an error
