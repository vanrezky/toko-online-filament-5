<?php

namespace App\Modules\Platform\Health\Services;

use App\Modules\Platform\Health\ValueObjects\HealthMonitorSnapshot;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults;
use Throwable;

final class HealthMonitorService
{
    private const CHECKS = ['application', 'database', 'redis', 'queue', 'disk'];

    public function __construct(private readonly ResultStore $resultStore) {}

    public function summary(): HealthMonitorSnapshot
    {
        try {
            return $this->normalize($this->resultStore->latestResults());
        } catch (Throwable) {
            return $this->unavailable();
        }
    }

    public function refresh(): HealthMonitorSnapshot
    {
        try {
            Artisan::call('health:check', ['--no-notification' => true]);
        } catch (Throwable) {
            return $this->unavailable();
        }

        return $this->summary();
    }

    private function normalize(?StoredCheckResults $results): HealthMonitorSnapshot
    {
        if ($results === null) {
            return $this->unavailable();
        }

        $stored = $results->storedCheckResults->keyBy('name');
        $checks = collect(self::CHECKS)->map(function (string $name) use ($stored): array {
            /** @var StoredCheckResult|null $result */
            $result = $stored->get($name);

            if ($result === null) {
                return $this->unknownCheck($name);
            }

            $status = match (strtolower($result->status)) {
                'ok' => 'healthy',
                'warning' => 'warning',
                'failed', 'crashed' => 'failed',
                default => 'unknown',
            };

            return [
                'name' => $name,
                'status' => $status,
                'message' => $this->safeMessage($result->notificationMessage),
                'meta' => $this->safeMeta($name, $result->meta),
            ];
        })->all();

        return new HealthMonitorSnapshot(
            overall: $this->overallStatus($checks),
            checks: $checks,
            available: true,
        );
    }

    private function unavailable(): HealthMonitorSnapshot
    {
        $checks = array_map(fn (string $name): array => $this->unknownCheck($name), self::CHECKS);

        return new HealthMonitorSnapshot('unknown', $checks, false);
    }

    private function unknownCheck(string $name): array
    {
        return [
            'name' => $name,
            'status' => 'unknown',
            'message' => __('admin/system-health-page.messages.unavailable'),
            'meta' => [],
        ];
    }

    /** @param array<int, array{name: string, status: string}> $checks */
    private function overallStatus(array $checks): string
    {
        $statuses = collect($checks)->pluck('status');

        return match (true) {
            $statuses->contains('failed') => 'failed',
            $statuses->contains('warning') => 'warning',
            $statuses->contains('unknown') => 'unknown',
            default => 'healthy',
        };
    }

    /** @param array<string, mixed> $meta */
    private function safeMeta(string $name, array $meta): array
    {
        $allowed = match ($name) {
            'application' => ['environment', 'laravel_version', 'php_version'],
            'database', 'redis' => ['connection_name'],
            'disk' => ['disk_space_used_percentage'],
            default => [],
        };

        return collect($meta)
            ->only($allowed)
            ->filter(fn (mixed $value): bool => is_scalar($value))
            ->all();
    }

    private function safeMessage(?string $message): ?string
    {
        if (blank($message)) {
            return null;
        }

        $message = preg_replace('/(password|secret|token)=?[^\s&]*/i', '$1=[redacted]', strip_tags($message));

        return Str::limit((string) $message, 180);
    }
}
