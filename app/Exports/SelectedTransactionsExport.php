<?php

namespace App\Exports;

use App\Enums\CourierCode;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SelectedTransactionsExport implements FromCollection, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected Collection $transactions
    ) {
        $this->transactions->loadMissing([
            'shippingDetails',
            'products',
        ]);
    }

    public function collection(): Collection
    {
        $rows = collect();

        $rows->push(['Laporan Transaksi Terpilih']);
        $rows->push([
            'Tanggal Export',
            '',
            $this->formatIndonesianDate(now()),
        ]);
        $rows->push([
            'Jumlah Transaksi',
            '',
            (string) $this->transactions->count(),
        ]);
        $rows->push([]);

        foreach ($this->transactions as $transaction) {
            /** @var Transaction $transaction */
            $rows->push([
                'ID Pesanan',
                'Status',
                'Tanggal Pemesanan',
                'Metode Bayar',
                'Metode Pengiriman',
                'Total',
            ]);

            $rows->push([
                $transaction->code ?? '-',
                (string) ($transaction->status?->getLabel() ?? $transaction->status?->value ?? '-'),
                $transaction->created_at ? $this->formatIndonesianDate($transaction->created_at) : '-',
                $transaction->payment_method ?? '-',
                $this->resolveShippingMethod($transaction),
                $this->formatCurrency($transaction->total_amount),
            ]);

            $rows->push([]);
            $rows->push([
                'Nama Product',
                'Jumlah',
                'Harga',
                'Subtotal',
            ]);

            if ($transaction->products->isEmpty()) {
                $rows->push([
                    'Tidak ada item transaksi tersimpan',
                    '-',
                    '-',
                    '-',
                ]);
            } else {
                foreach ($transaction->products as $product) {
                    /** @var TransactionProduct $product */
                    $rows->push([
                        $this->resolveTransactionProductName($product),
                        (int) $product->quantity,
                        $this->formatCurrency($product->price),
                        $this->formatCurrency($product->subtotal),
                    ]);
                }
            }

            $rows->push([]);
        }

        $rows->push([]);
        $rows->push([
            'TOTAL KESELURUHAN',
            '',
            '',
            '',
            '',
            $this->formatCurrency($this->transactions->sum(fn (Transaction $transaction) => (float) $transaction->total_amount)),
        ]);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $transactionStartRows = [];

                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A2:A3')->getFont()->setBold(true);

                for ($row = 1; $row <= $highestRow; $row++) {
                    $firstCell = (string) $sheet->getCell("A{$row}")->getValue();

                    if ($firstCell === 'ID Pesanan' || $firstCell === 'Nama Product') {
                        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                    }

                    if ($firstCell === 'TOTAL KESELURUHAN') {
                        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                    }

                    if ($firstCell === 'ID Pesanan') {
                        $transactionStartRows[] = $row;
                    }
                }

                foreach ($transactionStartRows as $index => $startRow) {
                    $nextStartRow = $transactionStartRows[$index + 1] ?? null;
                    $endRow = $nextStartRow ? $nextStartRow - 1 : $highestRow;

                    if ($endRow < $startRow) {
                        continue;
                    }

                    $sheet->getStyle("A{$startRow}:F{$endRow}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle('thin');
                }
            },
        ];
    }

    protected function resolveShippingMethod(Transaction $transaction): string
    {
        $details = $transaction->shippingDetails;

        if ($details->isEmpty()) {
            return '-';
        }

        $allPickup = $details->every(
            fn ($detail) => strtolower((string) $detail->courier_code) === CourierCode::PICKUP->value
        );

        if ($allPickup) {
            return 'Pickup';
        }

        return $details
            ->pluck('courier_name')
            ->filter()
            ->unique()
            ->implode(', ');
    }

    protected function resolveTransactionProductName(TransactionProduct $product): string
    {
        $productName = trim((string) ($product->product_name ?? ''));
        $variantName = trim((string) ($product->variant_name ?? ''));
        $productCode = trim((string) ($product->product_code ?? ''));

        if ($productName !== '' && $variantName !== '') {
            $label = "{$productName} ({$variantName})";

            if ($productCode !== '') {
                return "{$label} [{$productCode}]";
            }

            return $label;
        }

        if ($productName !== '') {
            if ($productCode !== '') {
                return "{$productName} [{$productCode}]";
            }

            return $productName;
        }

        if (filled($product->description)) {
            return (string) $product->description;
        }

        return 'Item transaksi historis #' . $product->id;
    }

    protected function formatCurrency(float|int|string|null $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }

    protected function formatIndonesianDate(CarbonInterface $date): string
    {
        return $date
            ->locale('id')
            ->translatedFormat('d F Y H:i:s');
    }
}
