<?php

namespace App\Modules\Platform\Tracing\Support;

/**
 * Strict allow-list of span attributes the tracing module may record.
 *
 * Everything the module exports must come from this list so sensitive data
 * (passwords, API keys, Authorization/cookie headers, card data, payment
 * secrets, raw tokens, request/response bodies, raw SQL, bound values) can
 * never leak into telemetry.
 */
final class TracingAttributes
{
    public const SERVICE_NAME = 'service.name';

    public const SERVICE_VERSION = 'service.version';

    public const DEPLOYMENT_ENVIRONMENT = 'deployment.environment';

    public const HTTP_REQUEST_METHOD = 'http.request.method';

    public const HTTP_ROUTE = 'http.route';

    public const HTTP_RESPONSE_STATUS_CODE = 'http.response.status_code';

    public const HTTP_DURATION_MS = 'http.duration_ms';

    public const URL_FULL = 'url.full';

    public const CORRELATION_ID = 'app.correlation_id';

    public const JOB_NAME = 'job.name';

    public const JOB_DURATION_MS = 'job.duration_ms';

    public const DB_SYSTEM = 'db.system';

    public const DB_OPERATION = 'db.operation';

    public const DB_NAMESPACE = 'db.namespace';

    public const DB_DURATION_MS = 'db.duration_ms';

    public const SPAN_KIND = 'span.kind';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::SERVICE_NAME,
            self::SERVICE_VERSION,
            self::DEPLOYMENT_ENVIRONMENT,
            self::HTTP_REQUEST_METHOD,
            self::HTTP_ROUTE,
            self::HTTP_RESPONSE_STATUS_CODE,
            self::HTTP_DURATION_MS,
            self::URL_FULL,
            self::CORRELATION_ID,
            self::JOB_NAME,
            self::JOB_DURATION_MS,
            self::DB_SYSTEM,
            self::DB_OPERATION,
            self::DB_NAMESPACE,
            self::DB_DURATION_MS,
            self::SPAN_KIND,
        ];
    }
}
