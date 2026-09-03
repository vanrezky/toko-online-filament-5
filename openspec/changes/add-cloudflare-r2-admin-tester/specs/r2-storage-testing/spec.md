## Purpose

Provides a safe, environment-backed way for administrators to verify that Cloudflare R2 storage is configured and can complete a temporary object lifecycle.

## ADDED Requirements

### Requirement: R2 configuration is environment-backed

The application MUST configure the R2 storage connection from the documented environment variables and MUST NOT require credentials to be stored in the database or entered through the admin UI.

#### Scenario: R2 disk uses environment configuration

- **WHEN** the application resolves the R2 filesystem disk
- **THEN** its S3-compatible connection uses the configured R2 access key, secret, region, bucket, endpoint, and optional public URL from environment values

#### Scenario: R2 credentials are absent from user-facing output

- **WHEN** the application displays or logs the result of an R2 test
- **THEN** access keys, secrets, and credential-bearing configuration values are not included

### Requirement: Administrator can run a temporary R2 connectivity test

The system MUST provide an authenticated admin action that tests the configured R2 disk by writing a unique temporary object, reading it back, verifying its contents, and deleting it before the action completes.

#### Scenario: Connectivity test succeeds

- **WHEN** an authorized administrator runs the R2 test and the configured disk can write, read, and delete the temporary object
- **THEN** the system reports a successful R2 connection and leaves no test object behind

#### Scenario: Connectivity test fails during an operation

- **WHEN** an authorized administrator runs the R2 test and any write, read, content verification, or delete operation fails
- **THEN** the system reports a safe failure status and attempts cleanup of the uniquely named temporary object

#### Scenario: Connectivity test does not use a fixed object name

- **WHEN** multiple administrators or test runs execute the R2 test
- **THEN** each run uses a unique temporary object identifier so that concurrent tests do not overwrite one another

### Requirement: R2 tester is restricted to authorized administrators

The R2 tester MUST be available only through the authenticated Filament admin panel and MUST enforce the application permission assigned to the tester page before allowing access or execution.

#### Scenario: Unauthorized user cannot access tester

- **WHEN** an unauthenticated or unauthorized user requests the R2 tester page or invokes its test action
- **THEN** the application denies access and does not perform an R2 operation

#### Scenario: Authorized user can access tester

- **WHEN** an authenticated administrator has the tester page permission
- **THEN** the administrator can view the tester and execute the connectivity test

### Requirement: Tester presents actionable but safe results

The admin UI MUST present a clear success or failure notification for the most recent test without exposing provider secrets or raw sensitive exception details.

#### Scenario: Successful result is shown

- **WHEN** the temporary object lifecycle completes successfully
- **THEN** the UI shows a success notification indicating that R2 is reachable and operational

#### Scenario: Failed result is shown

- **WHEN** the temporary object lifecycle cannot complete
- **THEN** the UI shows a failure notification with a non-sensitive explanation and does not reveal credentials, secret values, or full provider responses
