<?php

use Illuminate\Support\Env;

return [

    /*
    |--------------------------------------------------------------------------
    | Tracing Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for OpenTelemetry tracing. When false (the default) the
    | tracing service provider stays inert: no provider is built, no exporter
    | connection is established, and no spans are created.
    |
    */

    'enabled' => Env::get('TRACING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Service Identity
    |--------------------------------------------------------------------------
    */

    'service_name' => Env::get('OTEL_SERVICE_NAME', Env::get('APP_NAME', 'toko-online')),

    'service_version' => Env::get('OTEL_SERVICE_VERSION'),

    'environment' => Env::get('OTEL_RESOURCE_ATTRIBUTES', Env::get('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Sampler
    |--------------------------------------------------------------------------
    |
    | A ratio between 0 and 1. 1 records every span, 0 records none, and any
    | intermediate value samples deterministically by trace ID.
    |
    */

    'sampler_ratio' => (float) Env::get('OTEL_TRACES_SAMPLER_ARG', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Exporter
    |--------------------------------------------------------------------------
    |
    | "otlp" exports spans over OTLP HTTP to the configured endpoint. "memory"
    | keeps spans in-process and is used by tests. When tracing is enabled but
    | no exporter is configured, otlp is used.
    |
    */

    'exporter' => Env::get('OTEL_TRACES_EXPORTER', 'otlp'),

    'exporter_endpoint' => Env::get('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),

    'protocol' => Env::get('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'),

    /*
    |--------------------------------------------------------------------------
    | Database Persistence
    |--------------------------------------------------------------------------
    |
    | When true (and tracing is enabled) every ended span is also written to
    | the `tracing_spans` table in addition to the configured exporter. This
    | makes traces queryable from the application so the admin trace UI and
    | service map can be populated. Defaults to false so existing OTLP-only
    | behavior is unchanged. Persistence is best-effort: a DB failure is
    | reported and swallowed, never breaking the request.
    |
    */

    'persist' => Env::get('TRACING_PERSIST', false),

    /*
    |--------------------------------------------------------------------------
    | Slow Span Threshold
    |--------------------------------------------------------------------------
    |
    | Spans whose duration is equal to or greater than this number of
    | milliseconds are highlighted as slow in the trace detail page.
    |
    */

    'slow_span_threshold_ms' => (int) Env::get('TRACING_SLOW_SPAN_THRESHOLD_MS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Instrumentation Sources
    |--------------------------------------------------------------------------
    |
    | Individually toggle each instrumented source. Each defaults to true so a
    | single `TRACING_ENABLED=true` turns on the full set.
    |
    */

    'sources' => [
        'http' => Env::get('TRACING_HTTP', true),
        'http_client' => Env::get('TRACING_HTTP_CLIENT', true),
        'database' => Env::get('TRACING_DATABASE', true),
        'queue' => Env::get('TRACING_QUEUE', true),
    ],
];
