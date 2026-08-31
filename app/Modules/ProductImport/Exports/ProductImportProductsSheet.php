<?php

namespace App\Modules\ProductImport\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

final class ProductImportProductsSheet implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'Produk';
    }

    public function array(): array
    {
        return [[
            'Nama Produk*',
            'Deskripsi',
            'Kode Produk',
            'Kategori*',
            'Tipe Produk*',
            'Harga*',
            'Harga Diskon',
            'Stok*',
            'Berat',
            'Gudang Pengiriman',
            'Gambar URL',
        ]];
    }
}
