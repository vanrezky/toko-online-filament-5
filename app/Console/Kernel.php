<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Check expired unpaid orders every minute.
        $schedule->command('orders:check-expiry')
            ->everyMinute()
            ->onOneServer();

        // Mark overdue installment payments daily at 00:01
        $schedule->command('installments:mark-overdue')
            ->dailyAt('00:01')
            ->onOneServer();

        $schedule->command('health:queue-check-heartbeat')
            ->everyMinute()
            ->onOneServer()
            ->withoutOverlapping();

        $schedule->command('health:check --no-notification')
            ->everyFiveMinutes()
            ->onOneServer()
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
