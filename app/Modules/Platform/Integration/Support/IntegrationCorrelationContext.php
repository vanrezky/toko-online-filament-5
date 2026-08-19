<?php

namespace App\Modules\Platform\Integration\Support;

use Illuminate\Support\Str;

class IntegrationCorrelationContext
{
    private ?string $correlationId = null;

    public function id(?string $correlationId = null): string
    {
        return $this->correlationId ??= $correlationId ?: (string) Str::uuid();
    }

    public function set(string $correlationId): void
    {
        $this->correlationId = $correlationId;
    }
}
