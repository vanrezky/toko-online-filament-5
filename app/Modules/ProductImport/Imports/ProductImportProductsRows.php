<?php

namespace App\Modules\ProductImport\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

final class ProductImportProductsRows implements ToCollection
{
    /** @var Collection<int, array<int, mixed>> */
    public Collection $rows;

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}
