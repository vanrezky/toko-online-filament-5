<?php

namespace App\Modules\ProductImport\Exports;

use App\Models\Category;
use App\Models\Warehouse;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class ProductImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ProductImportProductsSheet,
            new ProductImportReferenceSheet(
                Category::query()->orderBy('name')->pluck('name')->all(),
                Warehouse::query()->orderBy('name')->pluck('name')->all(),
            ),
        ];
    }
}
