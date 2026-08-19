<?php

namespace App\Modules\Platform\Audit\Concerns;

use App\Modules\Platform\Audit\Services\AuditLogService;
use Spatie\Activitylog\Models\Activity;

trait HasPlatformAuditMetadata
{
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $context = app(AuditLogService::class)->requestMetadata();

        if ($context === []) {
            return;
        }

        $activity->properties = collect($activity->properties ?? [])->put('context', $context);
    }
}
