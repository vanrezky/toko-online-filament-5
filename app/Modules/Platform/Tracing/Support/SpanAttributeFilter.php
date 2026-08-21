<?php

namespace App\Modules\Platform\Tracing\Support;

/**
 * Filters a span's attributes down to the strict allow-list so sensitive
 * data can never be persisted.
 */
final class SpanAttributeFilter
{
    /** @var array<int, string> */
    private array $allowList;

    /**
     * @param  array<int, string>|null  $allowList
     */
    public function __construct(?array $allowList = null)
    {
        $this->allowList = $allowList ?? TracingAttributes::all();
    }

    /**
     * @param  iterable<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function filter(iterable $attributes): array
    {
        $allowed = [];

        foreach ($attributes as $key => $value) {
            if (in_array((string) $key, $this->allowList, true)) {
                $allowed[(string) $key] = $value;
            }
        }

        return $allowed;
    }
}
