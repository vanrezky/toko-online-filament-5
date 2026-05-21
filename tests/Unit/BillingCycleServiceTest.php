<?php

namespace Tests\Unit;

use App\Services\BillingCycleService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingCycleServiceTest extends TestCase
{
    #[Test]
    public function it_uses_current_cycle_when_transaction_day_is_on_or_before_cutoff(): void
    {
        $service = new BillingCycleService;

        $cycle = $service->resolveCycleMonthKey(Carbon::parse('2026-05-25 10:00:00'), 25);

        $this->assertSame('2026-05', $cycle);
    }

    #[Test]
    public function it_uses_next_cycle_when_transaction_day_is_after_cutoff(): void
    {
        $service = new BillingCycleService;

        $cycle = $service->resolveCycleMonthKey(Carbon::parse('2026-05-26 10:00:00'), 25);

        $this->assertSame('2026-06', $cycle);
    }

    #[Test]
    public function it_resolves_due_date_with_month_offset(): void
    {
        $service = new BillingCycleService;

        $dueDate = $service->resolveDueDate('2026-05', 5, 1);

        $this->assertSame('2026-06-05', $dueDate->toDateString());
    }

    #[Test]
    public function it_falls_back_to_end_of_month_for_invalid_due_day(): void
    {
        $service = new BillingCycleService;

        $dueDate = $service->resolveDueDate('2026-01', 31, 1);

        $this->assertSame('2026-02-28', $dueDate->toDateString());
    }
}
