<?php

return [
    'title' => 'Operasional Pembayaran',
    'navigation_label' => 'Operasional Pembayaran',
    'navigation_group' => 'Sistem',

    'sections' => [
        'customer_balance' => 'Saldo Pelanggan',
        'credit_checkout' => 'Kredit & Checkout',
        'billing_cycle' => 'Siklus Penagihan',
    ],

    'descriptions' => [
        'customer_balance' => 'Aktifkan wallet prepaid pelanggan untuk pembayaran penuh di checkout.',
        'credit_checkout' => 'Atur kelayakan kredit dan batas minimum pesanan cicilan.',
        'billing_cycle' => 'Perubahan tanggal jatuh tempo akan menyinkronkan tagihan berjalan yang relevan.',
    ],

    'fields' => [
        'balance_enabled' => 'Aktifkan Saldo Pelanggan',
        'balance_enabled_helper' => 'Menonaktifkan fitur akan mengunci akses saldo di admin dan storefront.',
        'enforce_credit_limit' => 'Gunakan Limit Kredit',
        'enforce_credit_limit_helper' => 'Batasi checkout pelanggan berdasarkan sisa limit kreditnya.',
        'installment_min_order_amount' => 'Minimum Pesanan Cicilan',
        'transaction_time_limit_minutes' => 'Batas Waktu Transaksi (menit)',
        'transaction_time_limit_minutes_helper' => 'Berlaku untuk semua pesanan baru; pesanan yang sudah dibuat tidak diubah.',
        'billing_cutoff_day' => 'Hari Cutoff',
        'billing_due_day' => 'Hari Jatuh Tempo',
        'billing_due_month_offset' => 'Offset Bulan Jatuh Tempo',
        'billing_due_month_offset_helper' => '0 berarti bulan yang sama, 1 berarti bulan berikutnya.',
    ],

    'notifications' => [
        'saved' => 'Pengaturan operasional pembayaran berhasil disimpan.',
        'billing_schedule_synced' => 'Pengaturan disimpan. :transactions tagihan full payment dan :installments tagihan cicilan diperbarui.',
    ],
];
