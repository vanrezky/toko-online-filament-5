<?php

return [

    'navigation_label' => 'Kategori Produk',
    'navigation_group' => 'Produk',

    'model_label' => 'Kategori',
    'plural_model_label' => 'Kategori',

    'pages' => [
        'list' => [
            'title' => 'Kategori Produk',
        ],
        'create' => [
            'title' => 'Buat Kategori',
        ],
        'edit' => [
            'title' => 'Edit Kategori',
        ],
    ],

    'tabs' => [
        'main' => 'Utama',
        'seo' => 'SEO',
    ],

    'fields' => [
        'name' => 'Nama Kategori',
        'image' => 'Gambar',
        'is_active' => 'Aktif',
        'is_featured' => 'Unggulan',
        'image_helper' => 'Rasio 1:1. Ukuran maksimal 1MB',
    ],

    'columns' => [
        'name' => 'Nama Kategori',
        'products_count' => 'Total Produk',
        'products_prefix' => 'Produk: ',
        'is_active' => 'Aktif',
        'is_featured' => 'Unggulan',
    ],

    'notifications' => [
        'cannot_delete' => 'Kategori tidak dapat dihapus',
        'cannot_delete_bulk' => 'Ada kategori yang tidak dapat dihapus',
        'activation_updated' => 'Status aktif berhasil diperbarui',
        'featured_updated' => 'Status unggulan berhasil diperbarui',
    ],

];
