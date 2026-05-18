<?php

return [

    'navigation_label' => 'Cicilan Anggota',
    'navigation_group' => 'Pelanggan',

    'model_label' => 'Cicilan Anggota',
    'plural_model_label' => 'Cicilan Anggota',

    'pages' => [
        'list' => [
            'title' => 'Cicilan Anggota',
        ],
        'view' => [
            'title' => 'Detail Cicilan Anggota',
        ],
    ],

    'sections' => [
        'installment_info' => 'Informasi Cicilan',
        'payment_progress' => 'Progress Pembayaran',
    ],

    'fields' => [
        'uuid' => 'Kode',
        'customer_id' => 'Anggota',
        'installment_plan_id' => 'Rencana Cicilan',
        'principal_amount' => 'Harga Pokok',
        'fee_amount' => 'Fee',
        'total_amount' => 'Total Cicilan',
        'monthly_amount' => 'Angsuran/Bulan',
        'tenor' => 'Tenor',
        'paid_installments' => 'Sudah Dibayar',
        'paid_amount' => 'Total Dibayar',
        'remaining_amount' => 'Sisa',
        'status' => 'Status',
        'start_date' => 'Tanggal Mulai',
        'expected_end_date' => 'Tgl Selesai',
    ],

    'status_options' => [
        'active' => 'Aktif',
        'completed' => 'Lunas',
        'overdue' => 'Terlambat',
        'defaulted' => 'Wanprestasi',
    ],

    'columns' => [
        'uuid' => 'Kode',
        'customer_full_name' => 'Anggota',
        'customer_level' => 'Level',
        'total_amount' => 'Total Cicilan',
        'monthly_amount' => 'Angsuran/Bulan',
        'tenor' => 'Tenor',
        'paid_installments' => 'Dibayar',
        'status' => 'Status',
        'start_date' => 'Tanggal Mulai',
    ],

    'filters' => [
        'status' => 'Status',
        'customer_level' => 'Level Anggota',
    ],

    'relation_managers' => [
        'payments_title' => 'Jadwal Pembayaran',
    ],

    'payments' => [
        'columns' => [
            'installment_number' => 'Ke',
            'amount' => 'Nominal',
            'due_date' => 'Jatuh Tempo',
            'status' => 'Status',
            'paid_amount' => 'Dibayar',
            'paid_date' => 'Tgl Bayar',
            'payment_method' => 'Metode',
        ],
        'actions' => [
            'mark_payroll' => 'Potong Gaji',
            'mark_paid' => 'Bayar Manual',
            'paid_amount' => 'Nominal',
            'notes' => 'Catatan',
        ],
        'status_options' => [
            'unpaid' => 'Belum Dibayar',
            'partial' => 'Sebagian',
            'paid' => 'Lunas',
            'overdue' => 'Terlambat',
        ],
    ],

];
