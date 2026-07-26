<?php

return [
    'navigation_label' => 'Flashsale',
    'model_label' => 'Flashsale',
    'plural_model_label' => 'Flashsale',

    'pages' => [
        'list' => [
            'title' => 'Daftar Flashsale',
        ],
        'create' => [
            'title' => 'Buat Flashsale',
        ],
        'edit' => [
            'title' => 'Edit Flashsale',
        ],
    ],

    'sections' => [
        'information' => 'Informasi Flashsale',
        'schedule' => 'Jadwal dan Status',
    ],

    'fields' => [
        'name' => 'Nama Flashsale',
        'description' => 'Deskripsi',
        'start_time' => 'Mulai',
        'end_time' => 'Berakhir',
        'is_active' => 'Aktif',
        'is_active_helper' => 'Visibilitas di frontend tetap mengikuti Template dengan kode flashsale.',
        'product' => 'Produk',
        'discount_percentage' => 'Diskon',
        'discount_percentage_helper' => 'Minimal diskon flashsale adalah 25%.',
        'stock' => 'Kuota Stok',
    ],

    'columns' => [
        'name' => 'Nama',
        'is_active' => 'Aktif',
        'start_time' => 'Mulai',
        'end_time' => 'Berakhir',
        'products' => 'Produk',
        'product' => 'Produk',
        'category' => 'Kategori',
        'discount_percentage' => 'Diskon',
        'stock' => 'Kuota Stok',
    ],
];
