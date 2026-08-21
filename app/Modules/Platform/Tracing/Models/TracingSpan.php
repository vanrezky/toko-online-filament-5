<?php

namespace App\Modules\Platform\Tracing\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * A single persisted OpenTelemetry span.
 *
 * Span and trace IDs are 32/16-char hex strings (OTel format), not UUIDs.
 * `end_ns` is null for spans that started but never ended, which is how
 * incomplete ("partial") traces are detected.
 */
class TracingSpan extends Model
{
    public const STATUS_UNSET = 'UNSET';

    public const STATUS_OK = 'OK';

    public const STATUS_ERROR = 'ERROR';

    protected $fillable = [
        'trace_id',
        'span_id',
        'parent_span_id',
        'name',
        'kind',
        'status_code',
        'status_description',
        'start_ns',
        'end_ns',
        'duration_ms',
        'attributes',
        'events',
        'correlation_id',
        'operation',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'events' => 'array',
            'start_ns' => 'integer',
            'end_ns' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    protected function statusCode(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : strtoupper($value),
        );
    }
}
