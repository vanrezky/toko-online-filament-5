<?php

namespace App\Modules\Platform\Support;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

final class Correlation
{
    public const KEY = 'correlation_id';

    /** @var array<int, string> */
    public const INBOUND_HEADERS = [
        'X-Request-ID',
        'X-Correlation-ID',
    ];

    public const OUTBOUND_HEADER = 'X-Correlation-ID';

    /** @var array<int, string> */
    public const RESPONSE_HEADERS = [
        'X-Request-ID',
        'X-Correlation-ID',
    ];

    /**
     * Maximum accepted length for an inbound identifier.
     *
     * Bounded to fit the `integration_logs.correlation_id` uuid column (char 36).
     */
    public const MAX_LENGTH = 36;

    private const SAFE_CHARS = 'A-Za-z0-9._:-';

    public static function id(): string
    {
        if ($existing = self::get()) {
            return $existing;
        }

        $id = (string) Str::uuid();
        self::set($id);

        return $id;
    }

    public static function get(): ?string
    {
        $value = Context::get(self::KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function set(string $correlationId): void
    {
        Context::add(self::KEY, $correlationId);
    }

    public static function reset(): void
    {
        Context::forget(self::KEY);
    }

    /** @return array<string, string> */
    public static function headers(): array
    {
        return [self::OUTBOUND_HEADER => self::id()];
    }

    /**
     * Normalize an inbound correlation identifier, or return null when it is
     * malformed or oversized so a new identifier can be generated instead.
     */
    public static function validate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim($value);

        if (strlen($value) > self::MAX_LENGTH) {
            return null;
        }

        return preg_match('/^['.self::SAFE_CHARS.']+$/', $value) === 1 ? $value : null;
    }
}
