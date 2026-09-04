<?php

namespace App\Modules\TransactionReceipt\Data;

final readonly class BatchReceiptResult
{
    public function __construct(
        public ?string $archivePath,
        public int $eligibleCount,
        public int $skippedCount,
    ) {}
}
