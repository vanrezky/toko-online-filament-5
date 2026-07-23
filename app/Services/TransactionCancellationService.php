<?php

namespace App\Services;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;

class TransactionCancellationService
{
    public function cancel(Transaction $transaction): void
    {
        if ($transaction->status === TransactionStatus::cancelled) {
            return;
        }

        $transaction->update([
            'status' => TransactionStatus::cancelled->value,
            'billing_status' => $transaction->payment_type === 'full'
                ? TransactionBillingStatus::cancelled->value
                : TransactionBillingStatus::not_applicable->value,
        ]);
    }
}
