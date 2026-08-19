<?php

namespace App\Modules\Platform\Integration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntegrationLog extends Model
{
    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    public const TYPE_API = 'api';

    public const TYPE_WEBHOOK = 'webhook';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'direction',
        'provider',
        'type',
        'method',
        'url',
        'endpoint',
        'request_headers',
        'request_body',
        'response_headers',
        'response_body',
        'status_code',
        'status',
        'duration_ms',
        'correlation_id',
        'subject_type',
        'subject_id',
        'error_class',
        'error_message',
        'attempt',
        'job_name',
        'queue',
        'payload_truncated',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'request_body' => 'array',
            'response_headers' => 'array',
            'response_body' => 'array',
            'payload_truncated' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
