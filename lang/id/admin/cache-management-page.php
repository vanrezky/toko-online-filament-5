<?php

return [
    'title' => 'Manajemen Cache',
    'navigation_label' => 'Manajemen Cache',
    'actions' => [
        'clear' => 'Bersihkan Cache Aplikasi',
        'clear_group' => 'Bersihkan :group',
    ],
    'confirmation' => [
        'heading' => 'Bersihkan cache aplikasi?',
        'description' => 'Hanya cache yang dibuat aplikasi yang akan diinvalidasi. Queue, Horizon, session, dan data Redis lain tidak akan dihapus.',
        'group_heading' => 'Bersihkan cache :group?',
        'group_description' => 'Hanya grup cache aplikasi yang dipilih yang akan diinvaliasi. Grup cache lain dan data runtime tetap aman.',
        'submit' => 'Bersihkan cache',
        'cancel' => 'Batal',
    ],
    'overview' => ['heading' => 'Ringkasan Cache Aplikasi'],
    'metadata' => [
        'store' => 'Cache Store',
        'default_store' => 'Default Store',
        'prefix' => 'Prefix',
        'connection' => 'Connection',
        'status' => 'Status',
    ],
    'status' => ['connected' => 'Terhubung', 'unavailable' => 'Tidak tersedia'],
    'managed' => [
        'heading' => 'Cache Aplikasi yang Dikelola',
        'description' => 'Grup cache aplikasi berikut dapat diinvaliasi dengan aman. Key dan value Redis tidak dapat dijelajahi dari halaman ini.',
    ],
    'messages' => [
        'unavailable' => 'Cache aplikasi tidak dapat diperiksa.',
        'probe_failed' => 'Probe cache aplikasi tidak mengembalikan hasil yang diharapkan.',
    ],
    'notifications' => [
        'cleared' => 'Cache aplikasi berhasil dibersihkan dengan aman.',
        'group_cleared' => 'Cache :group berhasil dibersihkan dengan aman.',
        'partial_failure' => 'Cache aplikasi hanya sebagian dibersihkan. Periksa audit log untuk grup yang terdampak.',
    ],
];
