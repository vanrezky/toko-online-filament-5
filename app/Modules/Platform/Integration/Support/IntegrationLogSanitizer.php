<?php

namespace App\Modules\Platform\Integration\Support;

use Illuminate\Support\Str;

class IntegrationLogSanitizer
{
    private const MASK = '********';

    /** @return array<int, string> */
    private function sensitiveKeys(): array
    {
        return array_map(
            static fn (string $key): string => strtolower($key),
            config('integration-logging.sensitive_keys', []),
        );
    }

    /** @return array<string|int, mixed> */
    public function sanitize(array $value): array
    {
        return $this->sanitizeValue($value);
    }

    /** @return array{value: array<string|int, mixed>|null, truncated: bool} */
    public function captureBody(mixed $body, ?string $contentType = null): array
    {
        if ($body === null || $body === '') {
            return ['value' => null, 'truncated' => false];
        }

        if ($this->isBinaryContentType($contentType)) {
            return ['value' => ['omitted' => 'binary_response'], 'truncated' => false];
        }

        $value = is_array($body) ? $this->sanitize($body) : $this->normalizeStringBody((string) $body);
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $limit = max(1, (int) config('integration-logging.payload_max_bytes', 65536));

        if ($encoded === false || strlen($encoded) <= $limit) {
            return ['value' => $value, 'truncated' => false];
        }

        return [
            'value' => [
                'truncated' => true,
                'preview' => Str::limit($encoded, $limit, ''),
            ],
            'truncated' => true,
        ];
    }

    /** @return array<string|int, mixed> */
    private function normalizeStringBody(string $body): array
    {
        $decoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->sanitize($decoded);
        }

        return ['content' => $body];
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];
        foreach ($value as $key => $item) {
            $sanitized[$key] = is_string($key) && $this->isSensitiveKey($key)
                ? self::MASK
                : $this->sanitizeValue($item);
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace('_', '-', $key));

        return in_array(strtolower($key), $this->sensitiveKeys(), true)
            || in_array($normalized, $this->sensitiveKeys(), true);
    }

    private function isBinaryContentType(?string $contentType): bool
    {
        if (! $contentType) {
            return false;
        }

        $contentType = strtolower($contentType);

        return str_contains($contentType, 'image/')
            || str_contains($contentType, 'application/pdf')
            || str_contains($contentType, 'application/zip')
            || str_contains($contentType, 'application/octet-stream')
            || str_contains($contentType, 'application/download');
    }
}
