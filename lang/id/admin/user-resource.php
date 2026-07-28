<?php

return [

    'navigation_label' => 'Pengguna',
    'navigation_group' => 'Sistem',

    'model_label' => 'Pengguna',
    'plural_model_label' => 'Pengguna',

    'pages' => [
        'list' => [
            'title' => 'Pengguna',
        ],
        'create' => [
            'title' => 'Buat Pengguna',
        ],
        'edit' => [
            'title' => 'Edit Pengguna',
        ],
        'view' => [
            'title' => 'Detail Pengguna',
        ],
    ],

    'section' => 'Informasi Pengguna',

    'fields' => [
        'name' => 'Nama',
        'email' => 'Email',
        'roles' => 'Peran',
        'password' => 'Password',
        'new_password' => 'Password Baru',
        'confirm_password' => 'Konfirmasi Password',
    ],

    'actions' => [
        'change_password' => 'Ubah Password',
    ],

    'notifications' => [
        'password_changed' => 'Password berhasil diubah.',
    ],

    'columns' => [
        'name' => 'Nama',
        'email' => 'Email',
        'email_verified_at' => 'Verifikasi Email',
        'created_at' => 'Dibuat',
        'updated_at' => 'Diperbarui',
    ],

    'filters' => [
        'verified' => 'Terverifikasi',
    ],

];
