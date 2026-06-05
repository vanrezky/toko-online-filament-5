<?php

return [

    'navigation_label' => 'Pesan Kontak',
    'navigation_group' => 'Logs',
    'model_label' => 'Pesan Kontak',
    'plural_model_label' => 'Pesan Kontak',

    'pages' => [
        'list' => [
            'title' => 'Pesan Kontak',
        ],
        'view' => [
            'title' => 'Detail Pesan Kontak',
        ],
    ],

    'fields' => [
        'name' => 'Nama',
        'email' => 'Email',
        'subject' => 'Subjek',
        'message' => 'Pesan',
        'is_read' => 'Tandai Sudah Dibaca',
        'read_at' => 'Dibaca Pada',
        'created_at' => 'Diterima Pada',
    ],

    'columns' => [
        'is_read' => 'Dibaca',
        'name' => 'Nama',
        'email' => 'Email',
        'subject' => 'Subjek',
        'message' => 'Pesan',
        'created_at' => 'Diterima',
    ],

    'filters' => [
        'is_read' => 'Status',
        'unread' => 'Belum Dibaca',
        'read' => 'Sudah Dibaca',
    ],

    'sections' => [
        'message_details' => 'Detail Pesan',
        'status' => 'Status',
    ],

    'empty_state' => [
        'heading' => 'Belum ada pesan',
        'description' => 'Pesan dari formulir kontak akan muncul di sini.',
    ],

];
