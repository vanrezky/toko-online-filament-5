<?php

namespace App\Services;

use Carbon\Carbon;

class BillingCycleService
{
    public function resolveCycleMonthKey(Carbon $transactionDate, int $cutoffDay): string
    {
        $cycleDate = $transactionDate->copy()->startOfMonth();

        if ((int) $transactionDate->day > $cutoffDay) {
            $cycleDate->addMonthNoOverflow();
        }

        return $cycleDate->format('Y-m');
    }

    public function resolveDueDate(string $cycleMonthKey, int $dueDay, int $monthOffset = 1): Carbon
    {
        $baseDate = Carbon::createFromFormat('Y-m', $cycleMonthKey)->startOfMonth();
        $dueMonth = $baseDate->copy()->addMonthsNoOverflow(max(0, $monthOffset));
        $daysInMonth = $dueMonth->daysInMonth;
        $day = min(max(1, $dueDay), $daysInMonth);

        return $dueMonth->copy()->day($day)->startOfDay();
    }
}
