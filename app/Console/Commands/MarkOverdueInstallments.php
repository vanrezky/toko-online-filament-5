<?php

namespace App\Console\Commands;

use App\Models\Installment;
use App\Models\InstallmentPayment;
use Illuminate\Console\Command;

class MarkOverdueInstallments extends Command
{
    protected $signature = 'installments:mark-overdue';

    protected $description = 'Mark installment payments that are overdue';

    public function handle(): int
    {
        $count = 0;

        InstallmentPayment::where('status', 'unpaid')
            ->where('due_date', '<', now()->startOfDay())
            ->each(function ($payment) use (&$count) {
                $payment->update(['status' => 'overdue']);

                if ($payment->installment->status === 'active') {
                    $payment->installment->update(['status' => 'overdue']);
                }

                $count++;
            });

        $this->info("Marked {$count} installment payments as overdue.");

        return self::SUCCESS;
    }
}