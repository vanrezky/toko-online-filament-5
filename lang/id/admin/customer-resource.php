<?php

return [

    'navigation_label' => 'Pelanggan',
    'navigation_group' => 'Pelanggan',
    'model_label' => 'Pelanggan',
    'plural_model_label' => 'Pelanggan',

    'pages' => [
        'list' => [
            'title' => 'Daftar Pelanggan',
        ],
        'create' => [
            'title' => 'Buat Pelanggan',
        ],
        'edit' => [
            'title' => 'Edit Pelanggan',
        ],
        'view' => [
            'title' => 'Profil Pelanggan',
        ],
    ],

    'fields' => [
        'profile_image' => 'Foto Profil',
        'profile_image_helper' => 'Rasio 1:1. Ukuran maksimal 1MB',
        'first_name' => 'Nama Depan',
        'last_name' => 'Nama Belakang',
        'email' => 'Email',
        'phone' => 'Telepon',
        'customer_level_id' => 'Level Anggota',
        'password' => 'Kata Sandi',
        'confirm_password' => 'Konfirmasi Kata Sandi',
        'credit_limit' => 'Custom Credit Limit',
        'effective_credit_limit' => 'Effective Credit Limit',
        'outstanding_balance' => 'Outstanding Balance',
        'remaining_credit_limit' => 'Sisa Limit Kredit',
    ],

    'sections' => [
        'general_information' => 'Informasi Umum',
        'password' => 'Kata Sandi',
        'credit_settings' => 'Pengaturan Kredit',
    ],

    'columns' => [
        'photo' => 'Foto',
        'name' => 'Nama',
        'email' => 'Email',
        'phone' => 'Telepon',
        'balance' => 'Saldo',
        'level' => 'Level',
        'credit_limit' => 'Credit Limit',
        'email_verification' => 'Verifikasi Email',
        'active' => 'Aktif',
        'created_at' => 'Dibuat',
        'updated_at' => 'Diperbarui',
    ],

    'filters' => [
        'email_verification' => 'Verifikasi Email',
        'verified' => 'Terverifikasi',
        'not_verified' => 'Belum Terverifikasi',
        'all' => 'Semua',
    ],

    'notifications' => [
        'verification_email_sent' => 'Email berhasil diverifikasi',
        'password_changed' => 'Kata sandi berhasil diubah',
        'balance_added' => 'Saldo berhasil ditambahkan',
        'balance_reduced' => 'Saldo berhasil dikurangi',
        'balance_update_failed' => 'Gagal memperbarui saldo',
    ],

    'actions' => [
        'verification_email' => 'Verifikasi Email',
        'profile' => 'Profil',
        'edit' => 'Edit',
        'delete' => 'Hapus',
        'change_password' => 'Ubah Kata Sandi',
        'new_password' => 'Kata Sandi Baru',
        'confirm_password' => 'Konfirmasi Kata Sandi',
    ],

    'profile' => [
        'name' => 'Nama',
        'member_since' => 'Terdaftar',
        'status' => 'Status',
        'email_verified' => 'Email Terverifikasi',
        'email_unverified' => 'Email Belum Terverifikasi',
        'customer_level' => 'Level Pelanggan',
        'credit_limit' => 'Batas Kredit',
        'outstanding' => 'Outstanding',
        'remaining_credit' => 'Sisa Kredit',
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        'yes' => 'Ya',
        'no' => 'Tidak',
    ],

    'tabs' => [
        'normal' => 'Normal',
    ],
];