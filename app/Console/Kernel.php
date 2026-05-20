<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Check for expired orders every 15 minutes
        $schedule->command('orders:check-expiry')
            ->everyFifteenMinutes()
            ->onOneServer();

        // Mark overdue installment payments daily at 00:01
        $schedule->command('installments:mark-overdue')
            ->dailyAt('00:01')
            ->onOneServer();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
