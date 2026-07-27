<?php

namespace App\Services;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionCancellationService
{
    public function __construct(
        private FlashsaleReservationService $flashsaleReservationService,
        private BalanceService $balanceService,
    )
    {
    }

    public function cancel(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->refresh();
            if ($transaction->status === TransactionStatus::cancelled) {
                return;
            }

            $transaction->update([
                'status' => TransactionStatus::cancelled->value,
                'billing_status' => $transaction->payment_type === 'full'
                    ? TransactionBillingStatus::cancelled->value
                    : TransactionBillingStatus::not_applicable->value,
            ]);

            $this->flashsaleReservationService->release($transaction);

            if ($transaction->payment_type === 'balance') {
                $this->balanceService->refund($transaction);
            }
        });
    }
}
