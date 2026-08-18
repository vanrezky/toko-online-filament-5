<?php

return [
    'title' => 'Queue Monitor',
    'navigation_label' => 'Queue Monitor',
    'actions' => [
        'open_horizon' => 'Buka Horizon',
    ],
    'cards' => [
        'horizon' => 'Kesehatan Queue',
        'pending' => 'Menunggu',
        'failed' => 'Gagal',
        'processed' => 'Diproses',
        'queues' => 'Queue Aktif',
        'workload' => 'Workload Queue',
    ],
    'status' => [
        'running' => 'Berjalan',
        'offline' => 'Offline',
        'unavailable' => 'Tidak tersedia',
        'no_workers' => 'Tidak ada worker',
    ],
    'value' => [
        'unavailable' => 'Tidak tersedia',
        'no_queues' => 'Tidak ada queue aktif',
        'no_workload' => 'Tidak ada workload',
    ],
    'workload' => [
        'queue' => 'Queue',
        'pending' => 'Menunggu',
        'wait' => 'Waktu tunggu',
        'processes' => 'Proses',
        'seconds' => ':count detik|:count detik',
    ],
];
