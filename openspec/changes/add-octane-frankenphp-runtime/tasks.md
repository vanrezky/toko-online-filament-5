## 1. Dependency and FrankenPHP image

- [x] 1.1 Add `laravel/octane` with the Sail Composer workflow and verify the lock diff contains only the required dependency resolution.
- [x] 1.2 Add a project-owned FrankenPHP Dockerfile with the PHP extensions and Composer runtime required by the locked application.
- [x] 1.3 Validate the FrankenPHP image with `composer check-platform-reqs` and confirm the Octane FrankenPHP driver can be resolved.

## 2. Optional Compose runtime

- [x] 2.1 Add an opt-in FrankenPHP Compose service with a configurable host port, healthy MySQL/Redis dependencies, the application bind mount, and no host Vite port mapping.
- [x] 2.2 Keep the default Sail service command, ports, volumes, and dependency behavior unchanged outside the optional profile.
- [x] 2.3 Add lifecycle commands for building, starting, checking, stopping, and returning to the default Sail runtime.

## 3. Long-running state safety

- [x] 3.1 Audit and harden the tracing job state so every started span is closed and no failed request or job leaves reusable state behind.
- [x] 3.2 Review request-time mail configuration mutation and make its lifecycle safe for repeated requests without changing the normal mail behavior.
- [x] 3.3 Add focused regression coverage for correlation, tracing cleanup, and repeated-request isolation.

## 4. Documentation and verification

- [x] 4.1 Document why Octane and FrankenPHP are used, how they differ from Sail, and the commands for each runtime lifecycle.
- [x] 4.2 Validate default and optional Compose configurations and build the FrankenPHP image.
- [x] 4.3 Run FrankenPHP startup and basic-request smoke checks, including repeated requests through the same worker.
- [x] 4.4 Run relevant backend/frontend validation and verify the default Sail workflow remains usable.
- [x] 4.5 Reconcile the implementation against Issue #1 and all OpenSpec requirements.
