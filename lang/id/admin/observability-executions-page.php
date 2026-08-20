<?php

return [
    'title' => 'Eksekusi Observability',
    'navigation_label' => 'Eksekusi',
    'search' => [
        'label' => 'Correlation ID',
        'placeholder' => 'Cari berdasarkan correlation ID…',
    ],
    'sections' => [
        'executions' => 'Eksekusi',
    ],
    'executions' => [
        'correlation_id' => 'Correlation ID',
        'last_event' => 'Event Terakhir',
        'open' => 'Buka',
    ],
    'value' => [
        'no_executions' => 'Tidak ada eksekusi yang cocok dengan pencarian saat ini.',
    ],
    'status' => ['pending' => 'Menunggu', 'success' => 'Berhasil', 'failed' => 'Gagal'],
];
