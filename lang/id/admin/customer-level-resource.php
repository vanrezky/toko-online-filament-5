<?php

return [

    'navigation_label' => 'Level Anggota',
    'navigation_group' => 'Pelanggan',

    'model_label' => 'Level Anggota',
    'plural_model_label' => 'Level Anggota',

    'pages' => [
        'list' => [
            'title' => 'Level Anggota',
        ],
        'create' => [
            'title' => 'Buat Level Anggota',
        ],
        'edit' => [
            'title' => 'Edit Level Anggota',
        ],
        'view' => [
            'title' => 'Detail Level Anggota',
        ],
    ],

    'sections' => [
        'level_data' => 'Data Level Anggota',
        'level_data_description' => 'Kelola level anggota dan batas kredit default',
        'credit_settings' => 'Pengaturan Kredit',
        'credit_settings_description' => 'Atur batas kredit default untuk level ini',
    ],

    'fields' => [
        'name' => 'Nama Level',
        'slug' => 'Slug',
        'description' => 'Deskripsi',
        'default_credit_limit' => 'Batas Kredit Default',
        'is_active' => 'Aktif',
        'is_active_helper' => 'Tidak bisa dinonaktifkan — level sudah memiliki anggota',
    ],

    'columns' => [
        'name' => 'Nama Level',
        'slug' => 'Slug',
        'default_credit_limit' => 'Batas Kredit Default',
        'is_active' => 'Aktif',
        'customers_count' => 'Jumlah Anggota',
        'created_at' => 'Dibuat Pada',
    ],

    'filters' => [
        'is_active' => 'Status Aktif',
        'is_active_placeholder' => 'Semua',
        'is_active_true' => 'Aktif',
        'is_active_false' => 'Tidak Aktif',
    ],

    'actions' => [
        'delete_tooltip' => 'Tidak bisa dihapus — sudah ada anggota',
        'delete_allowed_tooltip' => 'Hapus level',
    ],

    'notifications' => [
        'cannot_delete' => 'Level tidak dapat dihapus',
        'cannot_delete_bulk' => 'Ada level yang tidak dapat dihapus',
    ],

];
