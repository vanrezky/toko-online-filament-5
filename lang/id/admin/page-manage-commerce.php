<?php

return [
    'title' => 'Operasional Pembayaran',
    'navigation_label' => 'Operasional Pembayaran',
    'navigation_group' => 'Sistem',

    'sections' => [
        'credit_checkout' => 'Kredit & Checkout',
        'billing_cycle' => 'Siklus Penagihan',
    ],

    'descriptions' => [
        'credit_checkout' => 'Atur kelayakan kredit dan batas minimum pesanan cicilan.',
        'billing_cycle' => 'Perubahan tanggal jatuh tempo akan menyinkronkan tagihan berjalan yang relevan.',
    ],

    'fields' => [
        'enforce_credit_limit' => 'Gunakan Limit Kredit',
        'enforce_credit_limit_helper' => 'Batasi checkout pelanggan berdasarkan sisa limit kreditnya.',
        'installment_min_order_amount' => 'Minimum Pesanan Cicilan',
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
