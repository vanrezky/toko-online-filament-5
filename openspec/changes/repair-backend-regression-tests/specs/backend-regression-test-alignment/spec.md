## Purpose

Keep backend regression tests aligned with the current authorization, response, billing, audit, media, and authentication contracts without changing those contracts by assumption.

## ADDED Requirements

### Requirement: Authorization tests use the established technical-access contract

Contact Message resource tests MUST authenticate a superuser or grant the resource's required view permission before asserting successful access. Unauthorized access coverage MUST remain denied.

#### Scenario: Authorized administrator opens Contact Messages

- **WHEN** a superuser or user with the required Contact Message view permission requests the list or detail page
- **THEN** the response SHALL be successful

#### Scenario: Unprivileged administrator opens a protected resource

- **WHEN** a non-superuser without the required permission requests the protected resource
- **THEN** the response SHALL remain forbidden

### Requirement: Smoke tests assert current HTTP behavior

The home-page smoke test MUST assert the response behavior implemented by the current route and controller.

#### Scenario: Home page is available

- **WHEN** the test requests `/`
- **THEN** it SHALL assert the current successful response status rather than an obsolete redirect

### Requirement: Payroll tests target an explicit billing period

Payroll tests MUST make the billing month offset explicit when their fixture dates represent a direct due period, and MUST compare cast model values using the backed Enum value or Enum case as appropriate.

#### Scenario: Direct June due-period fixture is summarized

- **WHEN** a payroll test creates June due dates and configures a zero-month offset
- **THEN** the June summary SHALL include the expected installment and full-bill records

#### Scenario: Final payroll submission updates eligible records

- **WHEN** eligible June records are submitted using the same explicit period configuration
- **THEN** their persisted statuses and batch metadata SHALL match the final-submission contract

### Requirement: Audit tests isolate meaningful logged changes

Audit regression fixtures MUST use values representable by the database schema so unrelated precision normalization cannot appear as an allow-listed update.

#### Scenario: Description-only product update is not audited

- **WHEN** a product with database-compatible monetary values changes only its description
- **THEN** no new automatic activity SHALL be created

### Requirement: Media tests use the installed path API

Product media tests MUST distinguish absolute disk paths from paths relative to the disk root.

#### Scenario: Imported product media uses the upload prefix

- **WHEN** a public image is imported to the configured upload disk
- **THEN** the media record SHALL use the upload disk and its root-relative path SHALL be `uploads/products/{media-id}/product.png`

### Requirement: Store access tests use the current customer schema and registration contract

Private-store and public-registration tests MUST submit and persist the required `first_name` and `last_name` fields.

#### Scenario: Public store registration succeeds

- **WHEN** a guest submits valid first name, last name, email, and password while the store is public
- **THEN** registration SHALL redirect home and authenticate the customer

#### Scenario: Private store login remains available

- **WHEN** an existing valid customer with required name fields logs in while the store is private
- **THEN** login SHALL remain successful and registration SHALL remain blocked
