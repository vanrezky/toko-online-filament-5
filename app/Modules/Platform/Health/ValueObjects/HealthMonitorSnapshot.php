<?php

namespace App\Modules\Platform\Health\ValueObjects;

final readonly class HealthMonitorSnapshot
{
    /** @param array<int, array{name: string, status: string, message: ?string, meta: array<string, scalar>}> $checks */
    public function __construct(
        public string $overall,
        public array $checks,
        public bool $available,
    ) {}

    public function overallLabel(): string
    {
        return __('admin/system-health-page.status.'.$this->overall);
    }
}
