<?php

return [
    'title' => 'Traces Observability',
    'navigation_label' => 'Traces',
    'search' => [
        'label' => 'Cari',
        'placeholder' => 'Cari berdasarkan trace ID atau correlation ID…',
    ],
    'range' => [
        'label' => 'Rentang Waktu',
        '1h' => '1 jam terakhir',
        '24h' => '24 jam terakhir',
        '7d' => '7 hari terakhir',
        'all' => 'Semua waktu',
    ],
    'status' => [
        'label' => 'Status',
        'all' => 'Semua status',
        'UNSET' => 'Belum diatur',
        'OK' => 'OK',
        'ERROR' => 'Error',
    ],
    'operation' => [
        'label' => 'Operasi Akar',
        'placeholder' => 'Filter berdasarkan operasi…',
    ],
    'correlation' => [
        'label' => 'Correlation ID',
        'placeholder' => 'Filter berdasarkan correlation ID…',
    ],
    'duration' => [
        'min_label' => 'Durasi Min (ms)',
        'max_label' => 'Durasi Maks (ms)',
        'min_placeholder' => 'mis. 500',
        'max_placeholder' => 'mis. 5000',
    ],
    'errors' => [
        'label' => 'Ada error',
    ],
    'sections' => [
        'traces' => 'Traces',
    ],
    'traces' => [
        'timestamp' => 'Waktu',
        'trace_id' => 'Trace ID',
        'correlation_id' => 'Correlation ID',
        'operation' => 'Operasi',
        'status' => 'Status',
        'duration' => 'Durasi',
        'spans' => 'Span',
        'errors' => 'Error',
        'open' => 'Buka',
    ],
    'badge' => [
        'partial' => 'Parsial',
    ],
    'pagination' => [
        'total' => ':total traces',
        'prev' => 'Sebelumnya',
        'next' => 'Berikutnya',
    ],
    'value' => [
        'no_traces' => 'Tidak ada trace yang cocok dengan filter saat ini.',
    ],
];