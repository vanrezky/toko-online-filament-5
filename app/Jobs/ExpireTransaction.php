<?php

namespace App\Jobs;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use App\Services\TransactionCancellationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ExpireTransaction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $uniqueFor = 300;

    public function __construct(public string $transactionUuid) {}

    public function uniqueId(): string
    {
        return $this->transactionUuid;
    }

    public function handle(PaymentGatewayService $gateway, TransactionCancellationService $cancellation): void
    {
        $transaction = Transaction::query()->where('uuid', $this->transactionUuid)->first();

        if (! $transaction) {
            return;
        }

        if (in_array($transaction->payment_method, ['midtrans', 'stripe', 'xendit'], true)) {
            $provider = $gateway->getPaymentStatus($this->transactionUuid);

            if ($provider->isSuccess()) {
                return;
            }

            if ($provider->status === 'unknown') {
                $this->release(60);

                return;
            }
        }

        DB::transaction(function () use ($cancellation) {
            $transaction = Transaction::query()->where('uuid', $this->transactionUuid)->lockForUpdate()->first();

            if (! $transaction
                || ($transaction->billing_status?->value ?? (string) $transaction->billing_status) !== TransactionBillingStatus::pending->value
                || ($transaction->status?->value ?? (string) $transaction->status) !== TransactionStatus::packed->value
                || ! $transaction->timelimit
                || $transaction->timelimit->utc()->isFuture()) {
                return;
            }

            $cancellation->cancel($transaction);
            SendOrderExpiryNotification::dispatch($transaction->fresh());
        });
    }
}
