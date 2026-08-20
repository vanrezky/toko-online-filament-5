## 1. Observability service

- [x] 1.1 Create `App\Modules\Platform\Observability` module with an `ObservabilitySnapshot` value object exposing integration stats, provider performance, slow calls, recent errors, and queue/health summaries.
- [x] 1.2 Implement `ObservabilityService` computing aggregate integration metrics (`count`, failed count, `AVG`/`MAX` duration, provider `GROUP BY`, slow calls `LIMIT`, recent errors `LIMIT`) bounded by a `from`/`until` range using only existing indexes.
- [x] 1.3 Reuse `QueueMonitorService::snapshot()` and `HealthMonitorService::summary()` inside the service for the queue/health summaries.

## 2. Filament presentation

- [x] 2.1 Add `App\Filament\Clusters\ObservabilityCluster` in the `Platform` navigation group.
- [x] 2.2 Add `App\Filament\Pages\ObservabilityOverview` using `HasPageShield`, `canAccess()` = `is_super_user || can('View:Observability')`, `getViewData()` snapshot, and a time-range filter (24h / 7d / 30d presets + custom range).
- [x] 2.3 Add the Blade view rendering stat cards, provider performance table, slowest-calls table, recent-errors list, and correlation-ID drill-down links, each with empty states.
- [x] 2.4 Add Indonesian/English translation keys for the cluster and page.

## 3. Authorization

- [x] 3.1 Seed the `View:Observability` Shield permission in `ShieldSeeder` using the existing pattern.

## 4. Tests

- [x] 4.1 Add `ObservabilityServiceTest` covering metric aggregation, provider grouping, slow calls, recent errors, range bounding, and empty-range behavior.
- [x] 4.2 Add `ObservabilityOverviewAuthorizationTest` covering superuser, `View:Observability`-granted, and unauthorized access.

## 5. Validation

- [x] 5.1 Run Pint formatter and static analysis.
- [x] 5.2 Run the new tests plus the existing Platform/Filament suites.
- [x] 5.3 Run `npm run build` to confirm no frontend regressions.