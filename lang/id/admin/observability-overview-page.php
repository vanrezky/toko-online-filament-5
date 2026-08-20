<?php

return [
    'title' => 'Ikhtisar Observabilitas',
    'navigation_label' => 'Ikhtisar',
    'cluster' => ['navigation_label' => 'Observabilitas'],
    'filters' => ['range' => 'Rentang waktu', 'from' => 'Dari', 'until' => 'Sampai'],
    'ranges' => ['24h' => '24 jam terakhir', '7d' => '7 hari terakhir', '30d' => '30 hari terakhir', 'custom' => 'Rentang kustom'],
    'cards' => [
        'total_calls' => 'Panggilan Integrasi',
        'failed_calls' => 'Gagal',
        'avg_duration' => 'Durasi Rata-rata',
        'slowest' => 'Terlama',
        'queue_failed' => 'Antrean Gagal',
        'queue_processed' => 'Antrean Diproses',
        'health' => 'Kesehatan',
    ],
    'value' => [
        'unavailable' => 'Tidak tersedia',
        'duration' => ':ms md',
        'no_data' => 'Tidak ada aktivitas integrasi dalam rentang yang dipilih.',
        'no_slow_calls' => 'Tidak ada panggilan integrasi dengan durasi terukur dalam rentang yang dipilih.',
        'no_errors' => 'Tidak ada kegagalan integrasi dalam rentang yang dipilih.',
    ],
    'sections' => [
        'provider_performance' => 'Kinerja Provider',
        'slow_calls' => 'Panggilan Integrasi Terlama',
        'recent_errors' => 'Error Integrasi Terbaru',
    ],
    'provider' => ['provider' => 'Provider', 'calls' => 'Panggilan', 'failed' => 'Gagal', 'failed_rate' => 'Tingkat Gagal', 'avg_duration' => 'Durasi Rata-rata'],
    'slow_calls' => ['provider' => 'Provider', 'endpoint' => 'Endpoint', 'duration' => 'Durasi', 'status' => 'Status', 'correlation_id' => 'Correlation ID'],
    'recent_errors' => ['time' => 'Waktu', 'provider' => 'Provider', 'endpoint' => 'Endpoint', 'error' => 'Error', 'correlation_id' => 'Correlation ID'],
    'status' => ['pending' => 'Pending', 'success' => 'Sukses', 'failed' => 'Gagal'],
];