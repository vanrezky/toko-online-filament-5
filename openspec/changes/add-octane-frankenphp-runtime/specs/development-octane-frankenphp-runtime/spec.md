## Purpose

This capability provides an optional long-running Laravel runtime for developers who need to study and validate application behavior under a production-like PHP server. It must remain isolated from the existing default Sail workflow so normal development stays simple and predictable.

## ADDED Requirements

### Requirement: Optional FrankenPHP runtime

The project SHALL provide a documented, opt-in runtime that serves the Laravel application through Laravel Octane and FrankenPHP. The runtime SHALL expose a configurable host port and SHALL use the existing development database and Redis services.

#### Scenario: FrankenPHP runtime starts successfully

- **WHEN** a developer starts the documented FrankenPHP runtime with its required dependencies available
- **THEN** the application becomes reachable through the configured host port and returns a valid response for a basic application request

#### Scenario: Runtime dependencies are unavailable

- **WHEN** the FrankenPHP runtime is started while a required database or Redis dependency is unavailable
- **THEN** the runtime does not report a healthy application endpoint until the dependency becomes available

### Requirement: Default Sail workflow remains unchanged

The project SHALL keep the current Sail runtime as the default development mode. Enabling or stopping the optional FrankenPHP runtime SHALL NOT require changing the default application command, database volume, Redis volume, or host Vite workflow.

#### Scenario: Default development startup

- **WHEN** a developer starts the project using the existing default development command
- **THEN** Laravel is served through the existing Sail workflow and the application remains usable without FrankenPHP

#### Scenario: Optional mode is disabled

- **WHEN** the optional FrankenPHP runtime is not selected
- **THEN** no FrankenPHP process or host port is started by the default Compose workflow

### Requirement: Long-running requests remain isolated

The application SHALL prevent request-specific state, correlation data, temporary configuration, and active tracing scopes from leaking into a later request handled by the same long-running worker.

#### Scenario: Sequential requests use independent request state

- **WHEN** two requests with different correlation identifiers are handled sequentially by the same worker
- **THEN** each response and outbound operation uses only the correlation state belonging to its own request

#### Scenario: Request processing fails

- **WHEN** a request raises an exception after creating request-scoped tracing or correlation state
- **THEN** the worker remains able to handle a later request without reusing the failed request's active state

### Requirement: Runtime usage is documented

The project SHALL document why the optional runtime exists, when to use it, how it differs from default Sail, and how to start, inspect, stop, and reload it during development.

#### Scenario: Developer chooses a runtime

- **WHEN** a developer reads the development runtime documentation
- **THEN** the developer can identify the appropriate mode and execute the documented lifecycle commands without inferring hidden configuration
