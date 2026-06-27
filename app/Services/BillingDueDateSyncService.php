<?php

namespace App\Services;

use App\Enums\InstallmentPaymentStatus;
use App\Enums\TransactionBillingStatus;
use App\Models\InstallmentPayment;
use App\Models\Transaction;

class BillingDueDateSyncService
{
    public function __construct(
        protected BillingCycleService $billingCycleService
    ) {}

    public function syncForSettingsChange(array $previousSettings, array $currentSettings): array
    {
        $previousDueDay = $this->normalizeDay($previousSettings['billing_due_day'] ?? 5);
        $previousOffset = $this->normalizeOffset($previousSettings['billing_due_month_offset'] ?? 1);
        $currentDueDay = $this->normalizeDay($currentSettings['billing_due_day'] ?? 5);
        $currentOffset = $this->normalizeOffset($currentSettings['billing_due_month_offset'] ?? 1);

        if (
            ($previousDueDay === $currentDueDay)
            && ($previousOffset === $currentOffset)
        ) {
            return [
                'transactions_updated' => 0,
                'installment_payments_updated' => 0,
            ];
        }

        return [
            'transactions_updated' => $this->syncFullTransactions(
                $previousOffset,
                $currentDueDay,
                $currentOffset,
            ),
            'installment_payments_updated' => $this->syncInstallmentPayments(
                $previousOffset,
                $currentDueDay,
                $currentOffset,
            ),
        ];
    }

    protected function syncFullTransactions(int $previousOffset, int $currentDueDay, int $currentOffset): int
    {
        $updated = 0;

        Transaction::query()
            ->where('payment_type', 'full')
            ->whereNotNull('billing_due_date')
            ->whereIn('billing_status', [
                TransactionBillingStatus::pending->value,
                TransactionBillingStatus::submitted->value,
                TransactionBillingStatus::failed->value,
            ])
            ->chunkById(100, function ($transactions) use ($previousOffset, $currentDueDay, $currentOffset, &$updated): void {
                foreach ($transactions as $transaction) {
                    $currentDueDate = $transaction->billing_due_date?->copy()->startOfDay();

                    if (! $currentDueDate) {
                        continue;
                    }

                    $cycleMonthKey = $currentDueDate
                        ->copy()
                        ->startOfMonth()
                        ->subMonthsNoOverflow($previousOffset)
                        ->format('Y-m');

                    $newDueDate = $this->billingCycleService->resolveDueDate(
                        $cycleMonthKey,
                        $currentDueDay,
                        $currentOffset,
                    );

                    if ($currentDueDate->equalTo($newDueDate)) {
                        continue;
                    }

                    $transaction->forceFill([
                        'billing_due_date' => $newDueDate,
                    ])->save();

                    $updated++;
                }
            });

        return $updated;
    }

    protected function syncInstallmentPayments(int $previousOffset, int $currentDueDay, int $currentOffset): int
    {
        $updated = 0;

        InstallmentPayment::query()
            ->whereNotNull('due_date')
            ->whereIn('status', [
                InstallmentPaymentStatus::unpaid->value,
                InstallmentPaymentStatus::partial->value,
                InstallmentPaymentStatus::overdue->value,
            ])
            ->chunkById(100, function ($payments) use ($previousOffset, $currentDueDay, $currentOffset, &$updated): void {
                foreach ($payments as $payment) {
                    $currentDueDate = $payment->due_date?->copy()->startOfDay();

                    if (! $currentDueDate) {
                        continue;
                    }

                    $currentBillingMonth = $payment->billing_month?->copy()->startOfMonth()
                        ?? $currentDueDate->copy()->startOfMonth();

                    $cycleMonthKey = $currentBillingMonth
                        ->copy()
                        ->subMonthsNoOverflow($previousOffset)
                        ->format('Y-m');

                    $newDueDate = $this->billingCycleService->resolveDueDate(
                        $cycleMonthKey,
                        $currentDueDay,
                        $currentOffset,
                    );

                    $newBillingMonth = $newDueDate->copy()->startOfMonth();

                    if (
                        $currentDueDate->equalTo($newDueDate)
                        && $currentBillingMonth->equalTo($newBillingMonth)
                    ) {
                        continue;
                    }

                    $payment->forceFill([
                        'due_date' => $newDueDate,
                        'billing_month' => $newBillingMonth,
                    ])->save();

                    $updated++;
                }
            });

        return $updated;
    }

    protected function normalizeDay(int|string|null $day): int
    {
        return min(31, max(1, (int) $day));
    }

    protected function normalizeOffset(int|string|null $offset): int
    {
        return min(12, max(0, (int) $offset));
    }
}
