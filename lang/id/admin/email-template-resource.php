<?php

return [

    'navigation_label' => 'Template Email',
    'navigation_group' => 'Sistem',

    'model_label' => 'Template Email',
    'plural_model_label' => 'Template Email',

    'pages' => [
        'list' => [
            'title' => 'Template Email',
        ],
        'create' => [
            'title' => 'Buat Template Email',
        ],
        'edit' => [
            'title' => 'Edit Template Email',
        ],
    ],

    'sections' => [
        'template_information' => 'Informasi Template',
        'header_settings' => 'Pengaturan Header',
        'email_content' => 'Konten Email',
    ],

    'fields' => [
        'code' => 'Kode',
        'code_helper' => 'Identifier unik untuk template ini (contoh: reset_password, payment_success)',
        'name' => 'Nama',
        'is_active' => 'Aktif',
        'send_to_admin' => 'Kirim Salinan ke Admin',
        'send_to_admin_helper' => 'Kirim salinan email ini ke email admin yang dikonfigurasi di Pengaturan > Notifikasi',
        'header_title' => 'Judul Header',
        'header_title_helper' => 'Judul yang ditampilkan di header email (contoh: "Pembayaran Berhasil")',
        'header_gradient' => 'Gradient Header',
        'header_gradient_helper' => 'Warna gradient untuk header email. Kosongkan untuk default.',
        'subject' => 'Subjek',
        'subject_helper' => 'Subjek email. Gunakan {{placeholder}} untuk nilai dinamis.',
        'body' => 'Konten',
        'body_helper' => 'Konten body email. Gunakan {{placeholder}} untuk nilai dinamis. Style diterapkan secara otomatis.',
    ],

    'columns' => [
        'code' => 'Kode',
        'name' => 'Nama',
        'subject' => 'Subjek',
        'is_active' => 'Status',
        'send_to_admin' => 'Salinan Admin',
        'updated_at' => 'Diperbarui',
    ],

];
