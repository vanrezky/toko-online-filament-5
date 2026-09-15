## Purpose

Provides a reproducible, resource-bounded production runtime for the Laravel application while keeping secrets independently manageable and the existing development workflow isolated.

## ADDED Requirements

### Requirement: Production service stack

The system SHALL provide a production Compose configuration containing an HTTP application service, a queue worker, a scheduler, and MySQL, with Redis available as an explicitly documented optional service. The application service SHALL serve HTTP and HTTPS directly through the production application server without requiring Nginx or PHP-FPM on the host.

#### Scenario: Production stack starts with core services

- **WHEN** an operator starts the production Compose configuration with valid environment values
- **THEN** the application, worker, scheduler, and MySQL services start with healthchecks, restart policies, and persistent data volumes

#### Scenario: Optional Redis is enabled

- **WHEN** an operator enables the documented Redis profile and configures the application to use Redis
- **THEN** the Redis service starts with a bounded memory policy and the application services can resolve it by the Compose service name

#### Scenario: Production resource budget is applied

- **WHEN** the production Compose configuration is inspected or started for the 2 CPU / 2 GB target
- **THEN** application, worker, scheduler, database, and optional Redis resource limits are declared so the stack has a bounded resource budget

### Requirement: Production image

The production image SHALL contain only production Composer dependencies and compiled frontend assets, SHALL include the PHP extensions required by the application, and SHALL initialize required Laravel storage paths without requiring source files or development dependencies at runtime.

#### Scenario: Production image is built

- **WHEN** the production Docker image is built from the repository
- **THEN** Composer dependencies are installed without development packages, frontend assets are compiled, and the image build completes without a host PHP or Node runtime

#### Scenario: Production service starts from the image

- **WHEN** the application, worker, or scheduler service starts from the production image
- **THEN** the configured command runs with the application source, vendor dependencies, compiled assets, and writable Laravel runtime directories available

### Requirement: Independent environment configuration

The deployment workflow SHALL accept one GitHub Environment value per production configuration key and SHALL render one corresponding key/value entry per line in `.env.production`. It MUST NOT require a single multiline environment secret containing the complete file. Required values SHALL cause the deployment to fail before upload when missing, and generated environment files SHALL be handled as secret material without printing their values to logs.

#### Scenario: Individual values render successfully

- **WHEN** all required GitHub Environment values are present
- **THEN** the workflow renders `.env.production` with each configured key mapped to its own value and uploads it with restrictive file permissions

#### Scenario: A required value is missing

- **WHEN** a required production configuration value is absent
- **THEN** the workflow fails before deployment and identifies the missing key without exposing other secret values

#### Scenario: Existing multiline secret is not required

- **WHEN** the deployment is configured with individual values and no `PRODUCTION_ENV` blob
- **THEN** the workflow can complete its environment-rendering step without looking up or parsing `PRODUCTION_ENV`

### Requirement: Environment-driven OPcache

The production runtime SHALL expose explicit environment keys for OPcache enablement and its production values, including CLI enablement, memory consumption, interned strings buffer, maximum accelerated files, timestamp validation, revalidation frequency, saved comments, JIT mode, and JIT buffer size. The application and long-running worker processes SHALL apply those values at startup.

#### Scenario: OPcache production defaults are applied

- **WHEN** the production runtime starts with the documented OPcache values
- **THEN** OPcache is enabled for web and CLI execution, timestamp validation is disabled for immutable production code, and the configured memory/file/JIT values are loaded

#### Scenario: OPcache value is changed per environment

- **WHEN** an operator changes one documented `OPCACHE_*` environment value and recreates the production services
- **THEN** the new value is applied on the next service startup without editing the image source

### Requirement: Production operations documentation

The repository SHALL document first-time VPS preparation, required GitHub Environment values, deployment and scheduler operation, queue worker operation, health verification, rollback by immutable image tag, backup expectations, and safe handling of persistent volumes. The documentation SHALL state that development Sail and production Compose are separate workflows.

#### Scenario: Operator follows a first deployment

- **WHEN** an operator follows the documented production procedure on a prepared VPS
- **THEN** the operator can configure the individual environment values, start the stack, run migrations safely, verify service health, and understand where scheduled tasks and queue processing run

#### Scenario: Operator performs rollback

- **WHEN** a deployment must be reverted
- **THEN** the operator can select a known-good immutable image tag without deleting database, Redis, application storage, or FrankenPHP certificate volumes
