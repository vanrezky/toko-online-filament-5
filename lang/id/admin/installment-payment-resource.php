<?php

return [

    'navigation_label' => 'Pembayaran Cicilan',
    'navigation_group' => 'Operasional',
    'model_label' => 'Pembayaran Cicilan',
    'plural_model_label' => 'Pembayaran Cicilan',

    'pages' => [
        'list' => [
            'title' => 'Pembayaran Cicilan',
        ],
        'edit' => [
            'title' => 'Edit Pembayaran Cicilan',
        ],
        'view' => [
            'title' => 'Detail Pembayaran Cicilan',
        ],
    ],

    'sections' => [
        'payment_info' => 'Informasi Pembayaran Cicilan',
    ],

    'fields' => [
        'installment_code' => 'Kode Cicilan',
        'member' => 'Anggota',
        'installment_number' => 'Angsuran Ke',
        'amount' => 'Nominal',
        'due_date' => 'Jatuh Tempo',
        'status' => 'Status',
        'paid_amount' => 'Dibayar',
        'paid_date' => 'Tgl Bayar',
        'payment_method' => 'Metode',
        'notes' => 'Catatan',
    ],

    'columns' => [
        'installment_code' => 'Kode Cicilan',
        'member' => 'Anggota',
        'installment_number' => 'Angsuran Ke',
        'amount' => 'Nominal',
        'due_date' => 'Jatuh Tempo',
        'status' => 'Status',
        'paid_amount' => 'Dibayar',
        'paid_date' => 'Tgl Bayar',
        'payment_method' => 'Metode',
        'payroll_status' => 'Status Payroll',
    ],

    'filters' => [
        'customer' => 'Pelanggan',
        'transaction' => 'Transaksi',
        'status' => 'Status',
        'payment_method' => 'Metode Pembayaran',
        'payroll_status' => 'Status Payroll',
    ],

    'status_options' => [
        'unpaid' => 'Belum Bayar',
        'partial' => 'Sebagian',
        'paid' => 'Lunas',
        'overdue' => 'Terlambat',
    ],

    'payment_method_options' => [
        'payroll_deduction' => 'Potong Gaji',
        'manual' => 'Bayar Manual',
        'transfer' => 'Transfer',
    ],

    'payroll_status_options' => [
        'scheduled' => 'Terjadwal',
        'batched' => 'Dikelompokkan',
        'submitted' => 'Dikirim',
        'confirmed_paid' => 'Terkonfirmasi Dibayar',
        'failed' => 'Gagal',
    ],

];
