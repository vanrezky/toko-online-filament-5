<?php

return [

    'navigation_label' => 'Rencana Cicilan',
    'navigation_group' => 'Pelanggan',

    'model_label' => 'Rencana Cicilan',
    'plural_model_label' => 'Rencana Cicilan',

    'pages' => [
        'list' => [
            'title' => 'Rencana Cicilan',
        ],
        'create' => [
            'title' => 'Buat Rencana Cicilan',
        ],
        'edit' => [
            'title' => 'Edit Rencana Cicilan',
        ],
        'view' => [
            'title' => 'Detail Rencana Cicilan',
        ],
    ],

    'sections' => [
        'tenor' => 'Tenor Cicilan',
        'tenor_description' => 'Atur jangka waktu dan biaya cicilan',
        'status' => 'Status',
    ],

    'fields' => [
        'tenor' => 'Tenor (Bulan)',
        'fee_percentage' => 'Fee / Bunga (%)',
        'description' => 'Deskripsi',
        'is_active' => 'Aktif',
        'is_active_helper' => 'Tidak bisa dinonaktifkan — sudah ada cicilan yang menggunakan tenor ini',
    ],

    'columns' => [
        'tenor' => 'Tenor',
        'tenor_format' => 'bulan',
        'fee_percentage' => 'Fee/Bunga',
        'fee_format' => '%',
        'description' => 'Deskripsi',
        'is_active' => 'Aktif',
        'installments_count' => 'Cicilan Aktif',
        'created_at' => 'Dibuat',
    ],

    'filters' => [
        'is_active' => 'Status',
        'is_active_placeholder' => 'Semua',
        'is_active_true' => 'Aktif',
        'is_active_false' => 'Tidak Aktif',
    ],

    'actions' => [
        'delete_tooltip' => 'Tidak bisa dihapus — sudah ada cicilan',
        'delete_allowed_tooltip' => 'Hapus tenor',
    ],

    'notifications' => [
        'cannot_delete' => 'Rencana cicilan tidak dapat dihapus',
        'cannot_delete_bulk' => 'Ada rencana cicilan yang tidak dapat dihapus',
    ],

];
