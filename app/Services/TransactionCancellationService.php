<?php

namespace App\Services;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Modules\Platform\Audit\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class TransactionCancellationService
{
    public function __construct(
        private FlashsaleReservationService $flashsaleReservationService,
        private BalanceService $balanceService,
        private AuditLogService $auditLogService,
    ) {}

    public function cancel(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->refresh();
            if ($transaction->status === TransactionStatus::cancelled) {
                return;
            }

            $oldValues = $transaction->only(['status', 'billing_status']);
            $transaction->disableLogging()->update([
                'status' => TransactionStatus::cancelled->value,
                'billing_status' => $transaction->payment_type === 'full'
                    ? TransactionBillingStatus::cancelled->value
                    : TransactionBillingStatus::not_applicable->value,
            ]);

            $this->auditLogService->logBusinessAction(
                'transaction cancelled',
                $transaction,
                $oldValues,
                $transaction->only(['status', 'billing_status']),
            );

            $this->flashsaleReservationService->release($transaction);

            if ($transaction->payment_type === 'balance') {
                $this->balanceService->refund($transaction);
            }
        });
    }
}
