<?php

return [

    'navigation_label' => 'Log Email',
    'navigation_group' => 'Logs',
    'model_label' => 'Log Email',
    'plural_model_label' => 'Log Email',

    'pages' => [
        'list' => [
            'title' => 'Log Email',
        ],
        'view' => [
            'title' => 'Detail Log Email',
        ],
    ],

    'columns' => [
        'template_code' => 'Kode Template',
        'recipient_email' => 'Email Penerima',
        'subject' => 'Subjek',
        'status' => 'Status',
        'error_message' => 'Pesan Error',
        'sent_at' => 'Terkirim Pada',
        'created_at' => 'Dibuat Pada',
    ],

    'filters' => [
        'status' => 'Status',
        'template_code' => 'Template Email',
    ],

    'status_options' => [
        'pending' => 'Menunggu',
        'sent' => 'Terkirim',
        'failed' => 'Gagal',
    ],

    'sections' => [
        'email_information' => 'Informasi Email',
        'content' => 'Konten',
        'placeholders' => 'Placeholder yang Digunakan',
        'metadata' => 'Metadata',
    ],

    'entries' => [
        'template_code' => 'Kode Template',
        'recipient_email' => 'Email Penerima',
        'subject' => 'Subjek',
        'status' => 'Status',
        'body' => 'Konten Body',
        'placeholders' => 'Placeholder',
        'error_message' => 'Pesan Error',
        'sent_at' => 'Terkirim Pada',
        'created_at' => 'Dibuat Pada',
    ],

];
