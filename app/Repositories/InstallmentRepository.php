<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Installment;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;

final class InstallmentRepository
{
    /** @return Collection<int, Installment> */
    public function forCustomer(Customer $customer): Collection
    {
        return $customer->installments()
            ->with(['installmentPlan', 'payments', 'transaction'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function findForCustomer(Customer $customer, string $uuid): Installment
    {
        return Installment::query()
            ->where('uuid', $uuid)
            ->where('customer_id', $customer->getKey())
            ->with('installmentPlan')
            ->firstOrFail();
    }

    /** @return array{next_month: array<string, int|float|string|bool|null>|null, upcoming: list<array<string, int|float|string|bool|null>>} */
    public function monthlyBills(Customer $customer): array
    {
        $installmentPayments = Installment::query()
            ->where('customer_id', $customer->getKey())
            ->with([
                'transaction.products.product:id,name',
                'payments' => function (Relation $query): void {
                    $query->whereIn('status', ['unpaid', 'partial', 'overdue'])->orderBy('due_date');
                },
            ])
            ->get()
            ->flatMap(function (Installment $installment): Collection {
                $productName = (string) ($installment->transaction->products->first()?->product?->name ?? 'Produk');

                return $installment->payments->map(function ($payment) use ($installment, $productName): array {
                    $month = Carbon::parse($payment->billing_month ?? $payment->due_date);

                    return [
                        'month_key' => $month->format('Y-m'),
                        'month_label' => $month->translatedFormat('F Y'),
                        'description' => sprintf('Cicilan ke-%d %s', $payment->installment_number, $productName),
                        'amount' => (float) $payment->amount,
                        'status' => $payment->status,
                        'reference' => $installment->code,
                    ];
                });
            });

        $fullBills = Transaction::query()
            ->where('customer_id', $customer->getKey())
            ->where('payment_type', 'full')
            ->whereIn('billing_status', ['pending', 'submitted', 'failed'])
            ->whereNotNull('billing_due_date')
            ->with('products.product:id,name')
            ->get()
            ->map(function (Transaction $transaction): array {
                $month = Carbon::parse($transaction->billing_due_date);
                $productName = (string) ($transaction->products->first()?->product?->name ?? 'Produk');

                return [
                    'month_key' => $month->format('Y-m'),
                    'month_label' => $month->translatedFormat('F Y'),
                    'description' => sprintf('Tagihan penuh %s', $productName),
                    'amount' => (float) $transaction->total_amount,
                    'status' => $transaction->billing_status,
                    'reference' => $transaction->code,
                ];
            });

        $groups = $installmentPayments
            ->concat($fullBills)
            ->groupBy('month_key')
            ->sortKeys()
            ->map(fn (Collection $items): array => [
                'month_label' => $items->first()['month_label'],
                'items' => $items->values()->all(),
            ])
            ->values();

        return [
            'next_month' => $groups->first(),
            'upcoming' => $groups->slice(1)->values()->all(),
        ];
    }
}
