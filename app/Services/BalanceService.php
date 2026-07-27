<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use App\Settings\GeneralSettings;
use DomainException;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    public function isEnabled(): bool
    {
        return app(GeneralSettings::class)->balance_enabled;
    }

    public function topUp(Customer $customer, float $amount, string $notes, ?User $actor = null): Balance
    {
        $this->ensureEnabled();

        return $this->mutate($customer->id, $amount, Balance::TYPE_TOP_UP, $notes, $actor);
    }

    public function reduce(Customer $customer, float $amount, string $notes, ?User $actor = null): Balance
    {
        $this->ensureEnabled();

        return $this->mutate($customer->id, -$amount, Balance::TYPE_ADJUSTMENT_DEBIT, $notes, $actor);
    }

    public function pay(Transaction $transaction): Balance
    {
        $this->ensureEnabled();

        return $this->mutate(
            $transaction->customer_id,
            -$transaction->total_amount,
            Balance::TYPE_PURCHASE,
            "Pembayaran pesanan {$transaction->code}",
            null,
            $transaction,
        );
    }

    public function refund(Transaction $transaction): ?Balance
    {
        return DB::transaction(function () use ($transaction): ?Balance {
            Customer::query()->lockForUpdate()->findOrFail($transaction->customer_id);

            if (Balance::query()->where('transaction_id', $transaction->id)->where('type', Balance::TYPE_REFUND)->exists()) {
                return null;
            }

            return $this->mutate(
                $transaction->customer_id,
                $transaction->total_amount,
                Balance::TYPE_REFUND,
                "Refund pembatalan pesanan {$transaction->code}",
                null,
                $transaction,
            );
        });
    }

    private function mutate(int $customerId, float $signedAmount, string $type, string $notes, ?User $actor = null, ?Transaction $transaction = null): Balance
    {
        if ($signedAmount === 0.0) {
            throw new DomainException('Nominal saldo harus lebih besar dari nol.');
        }

        $customer = Customer::query()->lockForUpdate()->findOrFail($customerId);
        $before = round((float) $customer->balance, 2);
        $after = round($before + $signedAmount, 2);

        if ($after < 0) {
            throw new DomainException('Saldo tidak mencukupi.');
        }

        $customer->update(['balance' => $after]);

        return Balance::query()->create([
            'customer_id' => $customer->id,
            'performed_by_id' => $actor?->id,
            'transaction_id' => $transaction?->id,
            'amount' => abs($signedAmount),
            'charge' => 0,
            'balance_before' => $before,
            'post_balance' => $after,
            'trx_type' => $signedAmount > 0 ? '+' : '-',
            'type' => $type,
            'notes' => $notes,
            'remark' => $actor ? "Oleh {$actor->name}" : 'Sistem',
        ]);
    }

    private function ensureEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw new DomainException('Fitur saldo sedang tidak aktif.');
        }
    }
}
