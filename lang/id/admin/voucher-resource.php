<?php

return [

    'navigation_label' => 'Voucher',
    'navigation_group' => 'Master',

    'model_label' => 'Voucher',
    'plural_model_label' => 'Voucher',

    'pages' => [
        'list' => [
            'title' => 'Daftar Voucher',
        ],
        'create' => [
            'title' => 'Buat Voucher',
        ],
        'edit' => [
            'title' => 'Edit Voucher',
        ],
    ],

    'sections' => [
        'voucher_information' => 'Informasi Voucher',
        'validity_period' => 'Masa Berlaku',
        'other_information' => 'Informasi Lainnya',
    ],

    'fields' => [
        'name' => 'Nama Voucher',
        'description' => 'Deskripsi',
        'voucher_type' => 'Tipe Voucher',
        'voucher_for_product' => 'Voucher Untuk Produk',
        'voucher_for_shipping_cost' => 'Voucher Untuk Biaya Pengiriman',
        'discount_type' => 'Tipe Diskon',
        'fixed' => 'Tetap',
        'percentage' => 'Persentase',
        'product_type' => 'Tipe Produk',
        'all_product' => 'Semua Produk',
        'physical_product' => 'Produk Fisik',
        'digital_product' => 'Produk Digital',
        'code' => 'Kode Voucher',
        'discount_min' => 'Total Pesanan Minimum',
        'discount' => 'Nilai Potongan/Harga',
        'discount_percentage' => 'Persentase Diskon',
        'discount_max' => 'Diskon Maksimum',
        'start_at' => 'Mulai',
        'end_at' => 'Berakhir',
        'image' => 'Gambar Voucher',
        'image_helper' => 'Rasio 1:1. Ukuran maksimal 1MB',
        'category_id' => 'Kategori',
        'all_category' => 'Semua Kategori',
        'is_public' => 'Untuk publik',
        'is_active' => 'Aktif',
        'max_user_used' => 'Maksimal penggunaan per user',
        'back' => 'Kembali',
    ],

    'columns' => [
        'name' => 'Nama',
        'code' => 'Kode Voucher',
        'voucher_type' => 'Tipe',
        'discount_type' => 'Tipe Diskon',
        'product_type' => 'Tipe Produk',
        'discount' => 'Diskon',
        'validity_period' => 'Masa Berlaku',
        'is_active' => 'Aktif',
        'active' => 'Aktif',
        'created_at' => 'Dibuat',
        'updated_at' => 'Diperbarui',
    ],

    'notifications' => [
        'activation_updated' => 'Status aktif berhasil diperbarui',
    ],

];