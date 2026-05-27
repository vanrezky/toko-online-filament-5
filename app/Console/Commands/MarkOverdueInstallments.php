<?php

namespace App\Console\Commands;

use App\Enums\InstallmentStatus;
use App\Enums\InstallmentPaymentStatus;
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

        InstallmentPayment::where('status', InstallmentPaymentStatus::unpaid->value)
            ->where('due_date', '<', now()->startOfDay())
            ->each(function ($payment) use (&$count) {
                $payment->update(['status' => InstallmentPaymentStatus::overdue->value]);

                if ($payment->installment->status === 'active') {
                    $payment->installment->update(['status' => InstallmentStatus::Overdue->value]);
                }

                $count++;
            });

        $this->info("Marked {$count} installment payments as " . InstallmentStatus::Overdue->value . ".");

        return self::SUCCESS;
    }
}
