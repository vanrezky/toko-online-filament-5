<?php

return [
    'navigation_label' => 'SMTP Email',
    'navigation_group' => 'Pengaturan',

    'sections' => [
        'connection' => 'Koneksi SMTP',
        'credentials' => 'Kredensial SMTP',
        'test_email' => 'Uji Kirim Email',
    ],

    'descriptions' => [
        'connection' => 'Gunakan detail server dari penyedia email Anda.',
        'credentials' => 'Kredensial disimpan terenkripsi. Kosongkan kata sandi untuk mempertahankan nilai yang sudah tersimpan.',
    ],

    'fields' => [
        'mail_from' => 'Email Pengirim',
        'mail_host' => 'Host SMTP',
        'mail_port' => 'Port SMTP',
        'mail_encryption' => 'Enkripsi',
        'mail_username' => 'Username SMTP',
        'mail_password' => 'Kata Sandi SMTP',
        'mail_password_helper' => 'Kosongkan untuk tidak mengubah kata sandi yang tersimpan.',
    ],

    'actions' => [
        'test_email' => 'Kirim Email Uji',
        'recipient_email' => 'Email Penerima',
        'modal_submit' => 'Kirim',
        'modal_cancel' => 'Batal',
    ],

    'messages' => [
        'test_email_subject' => 'Uji Konfigurasi SMTP',
        'test_email_body' => 'Konfigurasi SMTP berhasil digunakan untuk mengirim email ini.',
    ],

    'notifications' => [
        'test_email_success' => 'Email uji berhasil dikirim.',
        'test_email_failed' => 'Email uji gagal dikirim',
    ],
];
