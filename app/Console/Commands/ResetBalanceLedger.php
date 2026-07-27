<?php

namespace App\Console\Commands;

use App\Models\Balance;
use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetBalanceLedger extends Command
{
    protected $signature = 'balance:reset-ledger {--force : Confirm permanent deletion of legacy balance history}';

    protected $description = 'Reset all customer wallet balances and delete the legacy balance ledger.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('This destructive command requires the --force option.');

            return self::FAILURE;
        }

        DB::transaction(function (): void {
            Balance::query()->delete();
            Customer::query()->update(['balance' => 0]);
        });

        $this->info('Legacy balance ledger deleted and all customer balances reset to zero.');

        return self::SUCCESS;
    }
}
