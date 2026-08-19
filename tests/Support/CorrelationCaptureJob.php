<?php

namespace Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Context;

class CorrelationCaptureJob implements ShouldQueue
{
    use Dispatchable;

    /** @var array<int, string|null> */
    public static array $captured = [];

    public function __construct(public ?string $override = null) {}

    public function handle(): void
    {
        self::$captured[] = $this->override ?? Context::get('correlation_id');
    }
}
