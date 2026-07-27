<?php

return [
    'title' => 'Dashboard',

    'filters' => [
        'title' => 'Rentang laporan',
        'description' => 'Pilih periode untuk memperbarui seluruh ringkasan di bawah.',
        'start_date' => 'Tanggal mulai',
        'end_date' => 'Tanggal selesai',
        'select_date' => 'Pilih tanggal',
    ],

    'stats' => [
        'revenue' => 'Total pendapatan',
        'orders' => 'Total pesanan',
        'new_customers' => 'Pelanggan baru',
        'average_order_value' => 'Rata-rata nilai pesanan',
        'change_from_previous' => ':percentage dari periode sebelumnya',
    ],

    'orders_by_status' => [
        'title' => 'Pesanan berdasarkan status',
    ],

    'recent_orders' => [
        'title' => 'Pesanan terbaru',
        'columns' => [
            'order_id' => 'ID pesanan',
            'customer' => 'Pelanggan',
            'amount' => 'Jumlah',
            'status' => 'Status',
            'date' => 'Tanggal',
        ],
    ],

    'low_stock' => [
        'title' => 'Stok menipis',
        'needs_restock' => ':count produk perlu diisi ulang.|:count produk perlu diisi ulang.',
        'sufficient' => 'Semua produk memiliki stok yang cukup.',
    ],
];
