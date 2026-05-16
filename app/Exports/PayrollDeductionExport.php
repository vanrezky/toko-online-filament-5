<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollDeductionExport implements FromCollection, WithHeadings, WithMapping
{
    protected int $month;
    protected int $year;
    protected array $data;

    public function __construct(int $month, int $year, array $data)
    {
        $this->month = $month;
        $this->year = $year;
        $this->data = $data;
    }

    public function collection(): Collection
    {
        $details = collect($this->data['details'] ?? []);

        return $details->map(function ($detail) {
            $customer = $detail['customer'];
            return collect([
                $customer->id,
                $customer->first_name . ' ' . $customer->last_name,
                $detail['level'],
                $detail['total_deduction'],
                $detail['active_installments'],
            ]);
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Level',
            'Total Potongan',
            'Cicilan Aktif',
        ];
    }

    public function map($row): array
    {
        return [
            $row[0],
            $row[1],
            $row[2],
            'Rp ' . number_format($row[3], 0, ',', '.'),
            $row[4],
        ];
    }
}