## Purpose

Provide a stable, strongly typed application boundary for Frontend use cases so HTTP adapters can remain thin while existing storefront, API, and transaction behavior stays unchanged.

## ADDED Requirements

### Requirement: Frontend endpoints preserve their existing contracts

The system SHALL preserve the existing Frontend route names, middleware, authorization decisions, validation outcomes, response formats, status codes, redirects, side effects, and transaction semantics while moving application orchestration behind a service boundary.

#### Scenario: Successful Frontend request

- **WHEN** a customer submits a valid request to an existing Frontend endpoint
- **THEN** the endpoint SHALL return the same successful page, JSON response, redirect, persisted result, and side effects as before the refactor

#### Scenario: Invalid or unauthorized Frontend request

- **WHEN** a customer submits invalid data or attempts an action they are not authorized to perform
- **THEN** the endpoint SHALL return the same validation or authorization behavior and SHALL not perform a partial business mutation

#### Scenario: Shared web and API contact flow

- **WHEN** the web or API contact-message endpoint is called with the same valid or invalid input
- **THEN** both adapters SHALL use the same application rule while preserving their existing response representation

### Requirement: Use cases are transport-independent

The application SHALL expose each migrated Frontend use case through a service operation that accepts validated typed values and domain models and returns a typed result or a documented domain exception without depending on an HTTP request or HTTP response object.

#### Scenario: Service called from an HTTP adapter

- **WHEN** a Frontend controller invokes a migrated use case
- **THEN** the controller SHALL pass validated values and required models to the service and SHALL format the returned result for Inertia, JSON, or redirect output

#### Scenario: Service called from another adapter

- **WHEN** a later API or Filament adapter invokes the same migrated use case
- **THEN** the service SHALL apply the same business rules without requiring an HTTP request or response instance

### Requirement: Persistence access has a concrete repository boundary

The application SHALL keep non-trivial Eloquent queries and persistence operations for migrated use cases behind concrete repositories with methods named for their domain behavior. Repositories SHALL not format HTTP responses or contain presentation concerns.

#### Scenario: Complex query is requested

- **WHEN** a service needs a reusable or multi-step query for a migrated use case
- **THEN** the service SHALL obtain the data through a concrete repository operation that returns a documented model, collection, nullable model, or scalar result

#### Scenario: Transactional persistence is performed

- **WHEN** a migrated use case creates, updates, deletes, locks, or reserves related records
- **THEN** the service SHALL own the transaction boundary and the repository SHALL perform only the requested persistence operation while preserving existing locking and ordering behavior

### Requirement: Migrated code uses explicit strong types

Every new or modified controller, service, and repository in this change SHALL declare strict scalar handling and explicit types for public properties, constructor dependencies, parameters, and return values. Complex arrays SHALL have a documented shape or a dedicated typed data object, and primary service or repository contracts SHALL NOT use `mixed`.

#### Scenario: Typed service invocation

- **WHEN** a caller invokes a migrated service operation with a value of the wrong scalar or object type
- **THEN** PHP strict typing SHALL reject the invalid call before the business operation is executed

#### Scenario: Typed collection or payload result

- **WHEN** a migrated service or repository returns a collection or structured payload
- **THEN** the returned item type or array shape SHALL be documented and stable for its callers

#### Scenario: Existing external gateway contract

- **WHEN** payment services use the existing gateway implementations
- **THEN** the existing gateway contract SHALL remain intact and this change SHALL add no new interface solely for a single service or repository implementation
