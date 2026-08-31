<?php

namespace App\Modules\ProductImport\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class ProductImportRows implements WithMultipleSheets
{
    public function __construct(
        public readonly ProductImportProductsRows $products = new ProductImportProductsRows,
    ) {}

    /** @return array<string, ProductImportProductsRows> */
    public function sheets(): array
    {
        return ['Produk' => $this->products];
    }
}
