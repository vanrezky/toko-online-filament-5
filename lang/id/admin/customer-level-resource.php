<?php

return [

    'navigation_label' => 'Level Pelanggan',
    'navigation_group' => 'Pelanggan',

    'model_label' => 'Level Pelanggan',
    'plural_model_label' => 'Level Pelanggan',

    'pages' => [
        'list' => [
            'title' => 'Level Pelanggan',
        ],
        'create' => [
            'title' => 'Buat Level Pelanggan',
        ],
        'edit' => [
            'title' => 'Edit Level Pelanggan',
        ],
        'view' => [
            'title' => 'Detail Level Pelanggan',
        ],
    ],

    'sections' => [
        'level_data' => 'Data Level Pelanggan',
        'level_data_description' => 'Kelola level pelanggan dan batas kredit default',
        'credit_settings' => 'Pengaturan Kredit',
        'credit_settings_description' => 'Atur batas kredit default untuk level ini',
    ],

    'fields' => [
        'name' => 'Nama Level',
        'slug' => 'Slug',
        'description' => 'Deskripsi',
        'default_credit_limit' => 'Batas Kredit Default',
        'is_active' => 'Aktif',
        'is_active_helper' => 'Tidak bisa dinonaktifkan — level sudah memiliki pelanggan',
    ],

    'columns' => [
        'name' => 'Nama Level',
        'slug' => 'Slug',
        'default_credit_limit' => 'Batas Kredit Default',
        'is_active' => 'Aktif',
        'customers_count' => 'Jumlah Pelanggan',
        'created_at' => 'Dibuat Pada',
    ],

    'filters' => [
        'is_active' => 'Status Aktif',
        'is_active_placeholder' => 'Semua',
        'is_active_true' => 'Aktif',
        'is_active_false' => 'Tidak Aktif',
    ],

    'actions' => [
        'delete_tooltip' => 'Tidak bisa dihapus — sudah ada pelanggan',
        'delete_allowed_tooltip' => 'Hapus level',
    ],

    'notifications' => [
        'cannot_delete' => 'Level tidak dapat dihapus',
        'cannot_delete_bulk' => 'Ada level yang tidak dapat dihapus',
    ],

];
