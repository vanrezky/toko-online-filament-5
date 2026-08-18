## Why

The application runs queued work but has no first-party operational visibility or durable Redis-backed queue runtime. Technical operators need a safe, high-level status view in Filament and the full Laravel Horizon dashboard to investigate queue health before delayed or failed jobs affect operations.

## What Changes

- Install and configure Laravel Horizon for the project's Laravel 11 application and make Redis the queue connection.
- Add a small, isolated Platform Queue module that provides fault-tolerant queue-health metrics through a service layer.
- Add an authorized Filament `Platform` / `Queue Monitor` page that summarizes health and links to Horizon rather than duplicating its technical UI.
- Restrict both the Filament summary and Horizon dashboard to the existing technical/superadmin authorization model.
- Document and align Horizon's process-manager invocation with the existing Supervisor-based deployment approach.

## Capabilities

### New Capabilities

- `horizon-queue-runtime`: Redis-backed Horizon configuration and protected Horizon dashboard operation.
- `platform-queue-monitoring`: Fault-tolerant queue-health metrics and the authorized Filament operational summary.

### Modified Capabilities

- None.

## Impact

- Affects Composer dependencies, queue and Horizon configuration, environment/deployment configuration, and Supervisor operations documentation.
- Adds isolated application code under `app/Modules/Platform/Queue`, a Filament page/widgets, translations, and tests.
- Changes the configured queue backend from database to Redis while preserving existing jobs and domain behavior.
