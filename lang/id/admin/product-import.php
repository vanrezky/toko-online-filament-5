<?php

return [
    'title' => 'Import Produk',
    'actions' => ['import' => 'Import Produk', 'download_template' => 'Download Template', 'validate' => 'Validasi File', 'submit' => 'Submit Produk'],
    'sections' => ['upload' => 'Upload Template', 'preview' => 'Review Hasil Validasi'],
    'upload_description' => 'Upload file Excel, lalu validasi sebelum produk dibuat. Hasil validasi berlaku selama 1 jam.',
    'fields' => ['file' => 'File Excel', 'name' => 'Nama', 'code' => 'Kode', 'category' => 'Kategori', 'product_type' => 'Tipe Produk', 'price' => 'Harga', 'sale_price' => 'Harga Diskon', 'stock' => 'Stok', 'weight' => 'Berat', 'warehouse' => 'Gudang', 'image_url' => 'Gambar URL'],
    'types' => ['physical' => 'Produk Fisik', 'digital' => 'Produk Digital'],
    'table' => ['row' => 'Baris', 'column' => 'Kolom', 'error' => 'Error'],
    'preview_expires' => 'Preview berlaku sampai :expires_at (maksimal 1 jam).',
    'preview_count' => ':count produk siap direview dan disubmit.',
    'confirmation' => ['heading' => 'Submit produk?', 'description' => 'Produk akan dibuat dari data hasil validasi. Token hanya dapat digunakan satu kali.'],
    'notifications' => [
        'file_required' => 'Silakan upload file Excel terlebih dahulu.',
        'invalid_file' => 'File tidak dapat dibaca. Gunakan template Excel yang sesuai.',
        'validation_failed' => 'Validasi gagal',
        'validation_failed_body' => 'Ditemukan :count kesalahan. Perbaiki file lalu validasi ulang.',
        'validation_success' => 'Validasi berhasil',
        'validation_success_body' => ':count produk siap direview.',
        'invalid_token' => 'Token import tidak valid atau sudah kedaluwarsa.',
        'busy' => 'Import sedang diproses. Silakan coba lagi.',
        'submit_failed' => 'Produk gagal dibuat. Preview masih dapat digunakan jika belum kedaluwarsa.',
        'submit_success' => 'Import produk berhasil',
        'submit_success_body' => ':count produk berhasil dibuat.',
    ],
    'validation' => [
        'required' => 'Wajib diisi.', 'required_physical' => 'Wajib diisi untuk produk fisik.', 'numeric' => 'Harus berupa angka nol atau lebih.', 'integer' => 'Harus berupa bilangan bulat nol atau lebih.',
        'sale_price' => 'Harga diskon harus lebih kecil dari harga normal.', 'product_type' => 'Tipe produk harus Fisik atau Digital.',
        'reference_not_found' => 'Nama tidak ditemukan atau tidak unik pada data referensi.', 'url' => 'Harus berupa URL yang valid.',
        'max_name' => 'Nama produk maksimal 255 karakter.', 'too_many_rows' => 'File melebihi batas maksimal :max baris.',
    ],
];
