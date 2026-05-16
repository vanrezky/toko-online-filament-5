<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollDeductionExport;

class PayrollExportService
{
    public function getMonthlyDeductions(int $month, int $year, ?int $customerLevelId = null): Collection
    {
        $query = InstallmentPayment::with(['installment.customer', 'installment.customer.customerLevel'])
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->whereNotNull('payment_method');

        $payments = $query->get();

        return $payments->map(function ($payment) {
            return [
                'customer' => $payment->installment->customer,
                'installment' => $payment->installment,
                'payment' => $payment,
                'amount' => $payment->amount,
                'due_date' => $payment->due_date,
            ];
        });
    }

    public function exportToExcel(int $month, int $year, ?int $customerLevelId = null)
    {
        $data = $this->getPayrollSummary($month, $year, $customerLevelId);

        return Excel::download(
            new PayrollDeductionExport($month, $year, $data),
            "potongan-gaji-{$this->getMonthName($month)}-" . substr($year, -2) . ".xlsx"
        );
    }

    public function getPayrollSummary(int $month, int $year, ?int $customerLevelId = null): array
    {
        $query = InstallmentPayment::with(['installment.customer.customerLevel'])
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->where('status', 'unpaid');

        if ($customerLevelId) {
            $query->whereHas('installment.customer', function ($q) use ($customerLevelId) {
                $q->where('customer_level_id', $customerLevelId);
            });
        }

        $payments = $query->get();

        $grouped = $payments->groupBy('installment.customer_id');

        $details = [];
        $totalAmount = 0;
        $totalCustomers = 0;

        foreach ($grouped as $customerId => $customerPayments) {
            $customer = $customerPayments->first()->installment->customer;
            $customerTotal = $customerPayments->sum('amount');

            $details[] = [
                'customer' => $customer,
                'level' => $customer->customerLevel?->name ?? 'N/A',
                'total_deduction' => $customerTotal,
                'active_installments' => $customerPayments->count(),
                'payments' => $customerPayments,
            ];

            $totalAmount += $customerTotal;
            $totalCustomers++;
        }

        return [
            'month' => $month,
            'year' => $year,
            'month_name' => $this->getMonthName($month),
            'total_customers' => $totalCustomers,
            'total_deduction' => $totalAmount,
            'total_installments' => $payments->count(),
            'details' => $details,
        ];
    }

    protected function getMonthName(int $month): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $months[$month] ?? '';
    }
}