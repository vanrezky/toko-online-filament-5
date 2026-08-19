<?php

namespace App\Modules\Platform\Integration\Support;

use App\Modules\Platform\Support\Correlation;
use Illuminate\Support\Str;

class IntegrationCorrelationContext
{
    private ?string $correlationId = null;

    public function id(?string $correlationId = null): string
    {
        if ($correlationId !== null && $correlationId !== '') {
            return $this->correlationId ??= $correlationId;
        }

        if ($current = $this->correlationId ?? Correlation::get()) {
            return $this->correlationId = $current;
        }

        $id = (string) Str::uuid();
        Correlation::set($id);

        return $this->correlationId = $id;
    }

    public function set(string $correlationId): void
    {
        $this->correlationId = $correlationId;
        Correlation::set($correlationId);
    }
}
