<?php

namespace App\Modules\Platform\Cache\ValueObjects;

final readonly class CacheManagementSnapshot
{
    /** @param array<string, string> $metadata */
    public function __construct(
        public string $status,
        public array $metadata,
        public ?string $message = null,
    ) {}

    public function statusLabel(): string
    {
        return __('admin/cache-management-page.status.'.$this->status);
    }
}
