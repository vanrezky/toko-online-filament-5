<?php

namespace App\Console\Commands;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Jobs\ExpireTransaction;
use App\Models\Transaction;
use Illuminate\Console\Command;

class CheckExpiredOrders extends Command
{
    protected $signature = 'orders:check-expiry';

    protected $description = 'Queue cancellation for expired unpaid orders';

    public function handle(): int
    {
        $this->info('Checking for expired orders...');

        $this->processExpiredOrders();

        $this->info('Expired orders check completed.');

        return Command::SUCCESS;
    }

    protected function processExpiredOrders(): void
    {
        // Find unpaid orders that have passed their timelimit
        $transactions = Transaction::where('status', TransactionStatus::packed->value)
            ->where('billing_status', TransactionBillingStatus::pending->value)
            ->whereNotNull('timelimit')
            ->where('timelimit', '<=', now('UTC'))
            ->get();

        $this->info("Found {$transactions->count()} expired orders.");

        foreach ($transactions as $transaction) {
            ExpireTransaction::dispatch($transaction->uuid);
            $this->info("Queued expiry check for order: {$transaction->uuid}");
        }
    }
}
