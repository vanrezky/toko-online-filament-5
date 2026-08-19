<?php

namespace App\Modules\Platform\Integration\Services;

use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Integration\Support\IntegrationCorrelationContext;
use App\Modules\Platform\Integration\Support\IntegrationLogSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class IntegrationLogService
{
    public function __construct(
        private readonly IntegrationLogSanitizer $sanitizer,
        private readonly IntegrationCorrelationContext $correlationContext,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function start(array $attributes): ?IntegrationLog
    {
        return $this->safely(function () use ($attributes): IntegrationLog {
            $requestBody = $this->sanitizer->captureBody(
                $attributes['request_body'] ?? null,
                $attributes['request_content_type'] ?? null,
            );

            $subject = $attributes['subject'] ?? null;
            unset($attributes['request_content_type'], $attributes['subject']);

            return IntegrationLog::query()->create([
                ...$attributes,
                'request_headers' => isset($attributes['request_headers'])
                    ? $this->sanitizer->sanitize((array) $attributes['request_headers'])
                    : null,
                'request_body' => $requestBody['value'],
                'payload_truncated' => $requestBody['truncated'],
                'status' => $attributes['status'] ?? IntegrationLog::STATUS_PENDING,
                'correlation_id' => $this->correlationContext->id($attributes['correlation_id'] ?? null),
                'subject_type' => $subject instanceof Model ? $subject::class : null,
                'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
                'started_at' => $attributes['started_at'] ?? now(),
            ]);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function finish(?IntegrationLog $integrationLog, array $attributes): void
    {
        if (! $integrationLog) {
            return;
        }

        $this->safely(function () use ($integrationLog, $attributes): bool {
            $responseBody = $this->sanitizer->captureBody(
                $attributes['response_body'] ?? null,
                $attributes['response_content_type'] ?? null,
            );

            return $integrationLog->update([
                'response_headers' => isset($attributes['response_headers'])
                    ? $this->sanitizer->sanitize((array) $attributes['response_headers'])
                    : $integrationLog->response_headers,
                'response_body' => array_key_exists('response_body', $attributes)
                    ? $responseBody['value']
                    : $integrationLog->response_body,
                'status_code' => $attributes['status_code'] ?? $integrationLog->status_code,
                'status' => $attributes['status'] ?? $integrationLog->status,
                'error_class' => $attributes['error_class'] ?? null,
                'error_message' => isset($attributes['error_message'])
                    ? Str::limit((string) $attributes['error_message'], 2000, '')
                    : null,
                'payload_truncated' => $integrationLog->payload_truncated || $responseBody['truncated'],
                'duration_ms' => $attributes['duration_ms'] ?? $this->durationMs($integrationLog->started_at),
                'finished_at' => $attributes['finished_at'] ?? now(),
            ]);
        });
    }

    public function fail(?IntegrationLog $integrationLog, Throwable $exception, ?int $statusCode = null): void
    {
        $this->finish($integrationLog, [
            'status' => IntegrationLog::STATUS_FAILED,
            'status_code' => $statusCode,
            'error_class' => $exception::class,
            'error_message' => $exception->getMessage(),
        ]);
    }

    private function durationMs(Carbon $startedAt): int
    {
        return max(0, (int) round($startedAt->diffInMicroseconds(now()) / 1000));
    }

    /** @template T @param callable(): T $callback @return T|null */
    private function safely(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            try {
                Log::warning('Integration log persistence failed', [
                    'exception' => $exception::class,
                ]);
            } catch (Throwable) {
                // Logging remains best-effort even when the configured log channel is unavailable.
            }

            return null;
        }
    }
}
