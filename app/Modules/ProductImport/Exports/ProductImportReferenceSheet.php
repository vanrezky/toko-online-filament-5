<?php

namespace App\Modules\ProductImport\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ProductImportReferenceSheet implements FromArray, WithTitle
{
    /** @param array<int,string> $categories @param array<int,string> $warehouses */
    public function __construct(
        private readonly array $categories,
        private readonly array $warehouses,
    ) {}

    public function title(): string
    {
        return 'Kategori dan Gudang';
    }

    public function array(): array
    {
        $rows = [
            ['Kategori', null, 'Gudang', null],
            ['NO', 'Nama', 'NO', 'Nama'],
        ];

        $max = max(count($this->categories), count($this->warehouses));
        for ($index = 0; $index < $max; $index++) {
            $rows[] = [
                $this->categories[$index] ?? null ? $index + 1 : null,
                $this->categories[$index] ?? null,
                $this->warehouses[$index] ?? null ? $index + 1 : null,
                $this->warehouses[$index] ?? null,
            ];
        }

        return $rows;
    }
}
