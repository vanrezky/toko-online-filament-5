<?php

namespace App\Services;

use App\Models\InstallmentPayment;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    public function submitAndExportFinal(int $month, int $year, ?int $customerLevelId = null): array
    {
        $batchReference = sprintf('PAYROLL-%04d%02d-%s', $year, $month, Str::upper(Str::random(6)));

        $fullBillsQuery = $this->baseFullBillsQuery($month, $year, $customerLevelId)
            ->whereIn('billing_status', ['pending', 'failed']);

        $fullBillIds = $fullBillsQuery->pluck('id');

        $installmentQuery = $this->baseInstallmentPaymentsQuery($month, $year, $customerLevelId)
            ->whereIn('payroll_status', ['scheduled', 'failed']);

        $installmentIds = $installmentQuery->pluck('id');

        $updatedFullBills = 0;
        $updatedInstallments = 0;

        if ($fullBillIds->isNotEmpty()) {
            $updatedFullBills = Transaction::query()
                ->whereIn('id', $fullBillIds)
                ->update(['billing_status' => 'submitted']);
        }

        if ($installmentIds->isNotEmpty()) {
            $updatedInstallments = InstallmentPayment::query()
                ->whereIn('id', $installmentIds)
                ->update([
                    'payroll_status' => 'submitted',
                    'payroll_batch_reference' => $batchReference,
                    'submitted_at' => now(),
                ]);
        }

        $totalUpdated = $updatedFullBills + $updatedInstallments;

        $response = $this->exportToExcel($month, $year, $customerLevelId);

        return [
            'response' => $response,
            'batch_reference' => $batchReference,
            'updated_full_bills' => $updatedFullBills,
            'updated_installments' => $updatedInstallments,
            'total_updated' => $totalUpdated,
        ];
    }

    public function getPayrollSummary(int $month, int $year, ?int $customerLevelId = null): array
    {
        $installmentPayments = $this->getInstallmentBillItems($month, $year, $customerLevelId);
        $fullBills = $this->getFullBillItems($month, $year, $customerLevelId);

        $allItems = $installmentPayments->concat($fullBills);
        $grouped = $allItems->groupBy('customer_id');

        $details = [];
        $totalAmount = 0;
        $totalCustomers = 0;

        foreach ($grouped as $customerId => $customerItems) {
            $customer = $customerItems->first()['customer'];
            $customerTotal = $customerItems->sum('amount');
            $installmentCount = $customerItems->where('type', 'installment')->count();

            $details[] = [
                'customer' => $customer,
                'level' => $customer->customerLevel?->name ?? 'N/A',
                'total_deduction' => $customerTotal,
                'active_installments' => $installmentCount,
                'references' => $customerItems
                    ->map(fn ($item) => $item['reference'])
                    ->filter()
                    ->unique()
                    ->implode(', '),
                'payments' => $customerItems,
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
            'total_installments' => $installmentPayments->count(),
            'total_full_bills' => $fullBills->count(),
            'total_bill_items' => $allItems->count(),
            'details' => $details,
        ];
    }

    protected function getInstallmentBillItems(int $month, int $year, ?int $customerLevelId = null): Collection
    {
        $query = $this->baseInstallmentPaymentsQuery($month, $year, $customerLevelId);

        return $query->get()->map(function (InstallmentPayment $payment) {
            return [
                'type' => 'installment',
                'customer_id' => $payment->installment->customer_id,
                'customer' => $payment->installment->customer,
                'amount' => (float) $payment->amount,
                'reference' => $payment->installment?->transaction?->code ?? $payment->installment?->code,
                'transaction_uuid' => $payment->installment?->transaction?->uuid,
            ];
        });
    }

    protected function getFullBillItems(int $month, int $year, ?int $customerLevelId = null): Collection
    {
        $query = $this->baseFullBillsQuery($month, $year, $customerLevelId);

        return $query->get()->map(function (Transaction $transaction) {
            return [
                'type' => 'full',
                'customer_id' => $transaction->customer_id,
                'customer' => $transaction->customer,
                'amount' => (float) $transaction->total_amount,
                'reference' => $transaction->code,
                'transaction_uuid' => $transaction->uuid,
            ];
        });
    }

    protected function baseInstallmentPaymentsQuery(int $month, int $year, ?int $customerLevelId = null)
    {
        $query = InstallmentPayment::with(['installment.customer.customerLevel', 'installment.transaction'])
            ->whereYear('due_date', $year)
            ->whereMonth('due_date', $month)
            ->whereIn('status', ['unpaid', 'partial', 'overdue']);

        if ($customerLevelId) {
            $query->whereHas('installment.customer', function ($q) use ($customerLevelId) {
                $q->where('customer_level_id', $customerLevelId);
            });
        }

        return $query;
    }

    protected function baseFullBillsQuery(int $month, int $year, ?int $customerLevelId = null)
    {
        $query = Transaction::with(['customer.customerLevel'])
            ->where('payment_type', 'full')
            ->whereIn('billing_status', ['pending', 'submitted', 'failed'])
            ->whereNotNull('billing_due_date')
            ->whereYear('billing_due_date', $year)
            ->whereMonth('billing_due_date', $month);

        if ($customerLevelId) {
            $query->whereHas('customer', function ($q) use ($customerLevelId) {
                $q->where('customer_level_id', $customerLevelId);
            });
        }

        return $query;
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
