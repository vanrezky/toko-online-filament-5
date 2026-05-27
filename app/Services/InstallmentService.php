<?php

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class InstallmentService
{
    public function createInstallment(Transaction $transaction, InstallmentPlan $plan): Installment
    {
        $principalAmount = $transaction->total_amount;
        $totalAmount = $plan->calculateTotal($principalAmount);
        $monthlyAmount = $plan->calculateMonthly($principalAmount);
        $feeAmount = $totalAmount - $principalAmount;
        $startDate = now();
        $expectedEndDate = now()->addMonths($plan->tenor);

        $installment = Installment::create([
            'transaction_id' => $transaction->id,
            'customer_id' => $transaction->customer_id,
            'installment_plan_id' => $plan->id,
            'principal_amount' => $principalAmount,
            'fee_amount' => $feeAmount,
            'total_amount' => $totalAmount,
            'monthly_amount' => $monthlyAmount,
            'tenor' => $plan->tenor,
            'paid_amount' => 0,
            'paid_installments' => 0,
            'status' => InstallmentStatus::Active->value,
            'start_date' => $startDate,
            'expected_end_date' => $expectedEndDate,
        ]);

        $this->generatePaymentSchedule($installment);

        return $installment;
    }

    public function generatePaymentSchedule(Installment $installment): Collection
    {
        $payments = [];

        for ($i = 1; $i <= $installment->tenor; $i++) {
            $dueDate = $installment->start_date->copy()->addMonths($i);

            $payments[] = InstallmentPayment::create([
                'installment_id' => $installment->id,
                'installment_number' => $i,
                'amount' => $installment->monthly_amount,
                'due_date' => $dueDate,
                'billing_month' => $dueDate->copy()->startOfMonth(),
                'paid_amount' => 0,
                'paid_date' => null,
                'payment_method' => null,
                'collection_method' => 'payroll_deduction',
                'status' => 'unpaid',
                'payroll_status' => 'scheduled',
                'notes' => null,
            ]);
        }

        return collect($payments);
    }

    public function processPayment(InstallmentPayment $payment, float $amount, string $method): void
    {
        $payment->markAsPaid($amount, $method);
    }

    public function markAsPayrollDeduction(InstallmentPayment $payment): void
    {
        $payment->update([
            'payment_method' => 'payroll_deduction',
            'collection_method' => 'payroll_deduction',
        ]);
    }

    public function markOverduePayments(): int
    {
        $count = 0;

        InstallmentPayment::where('status', 'unpaid')
            ->where('due_date', '<', now()->startOfDay())
            ->each(function ($payment) use (&$count) {
                $payment->update(['status' => 'overdue']);

                if ($payment->installment->status === 'active') {
                    $payment->installment->update(['status' => 'overdue']);
                }

                $count++;
            });

        return $count;
    }

    public function getCustomerInstallments(Customer $customer): Collection
    {
        return $customer->installments()
            ->with(['installmentPlan', 'payments'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getInstallmentSchedule(Installment $installment): Collection
    {
        return $installment->payments()
            ->orderBy('installment_number')
            ->get();
    }
}
