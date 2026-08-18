<?php

return [
    'title' => 'System Health',
    'navigation_label' => 'System Health',
    'actions' => ['refresh' => 'Refresh Health'],
    'overall' => ['heading' => 'Status Sistem Keseluruhan'],
    'status' => ['healthy' => 'Sehat', 'warning' => 'Peringatan', 'failed' => 'Gagal', 'unknown' => 'Tidak diketahui'],
    'checks' => ['application' => 'Aplikasi', 'database' => 'Database', 'redis' => 'Redis', 'queue' => 'Queue', 'disk' => 'Disk'],
    'meta' => ['environment' => 'Environment', 'laravel_version' => 'Laravel', 'php_version' => 'PHP', 'connection_name' => 'Connection', 'disk_space_used_percentage' => 'Penggunaan disk'],
    'messages' => ['unavailable' => 'Hasil health belum tersedia atau tidak dapat dibaca.'],
    'notifications' => ['refreshed' => 'Health check berhasil dijalankan.'],
];
