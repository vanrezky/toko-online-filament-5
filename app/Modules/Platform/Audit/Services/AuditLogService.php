<?php

namespace App\Modules\Platform\Audit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

final class AuditLogService
{
    /** @return array<string, string> */
    public function requestMetadata(): array
    {
        if (app()->runningInConsole() || ! app()->bound('request')) {
            return [];
        }

        $request = app(Request::class);

        return array_filter([
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => Str::limit($request->fullUrl(), 500, ''),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ], fn (?string $value): bool => filled($value));
    }

    /** @param array<string, mixed> $old @param array<string, mixed> $attributes */
    public function logBusinessAction(string $description, Model $subject, array $old = [], array $attributes = []): Activity
    {
        $properties = array_filter([
            'old' => $old,
            'attributes' => $attributes,
            'context' => $this->requestMetadata(),
        ], fn (array $value): bool => $value !== []);

        $logger = activity('platform')
            ->event('business')
            ->performedOn($subject)
            ->withProperties($properties);

        if ($actor = auth()->user()) {
            $logger->causedBy($actor);
        }

        return $logger->log($description);
    }

    public function actorLabel(Activity $activity): string
    {
        return (string) ($activity->causer?->name ?? $activity->causer?->email ?? __('admin/audit-log-resource.system'));
    }

    public function subjectLabel(Activity $activity): string
    {
        $subject = $activity->subject;
        $identifier = $subject?->getAttribute('code') ?? $subject?->getKey() ?? $activity->subject_id;

        return class_basename((string) $activity->subject_type).' #'.$identifier;
    }

    public function displayValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('admin/audit-log-resource.values.true') : __('admin/audit-log-resource.values.false');
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
        }

        return (string) $value;
    }

    /** @return array<int, array{field: string, old: mixed, new: mixed}> */
    public function changes(Activity $activity): array
    {
        $properties = $activity->properties?->all() ?? [];
        $old = Arr::get($properties, 'old', []);
        $attributes = Arr::get($properties, 'attributes', []);

        return collect(array_unique([...array_keys($old), ...array_keys($attributes)]))
            ->map(fn (string $field): array => [
                'field' => $field,
                'old' => Arr::get($old, $field),
                'new' => Arr::get($attributes, $field),
            ])
            ->values()
            ->all();
    }
}
