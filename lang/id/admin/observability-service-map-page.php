<?php

return [
    'title' => 'Service Map Observability',
    'navigation_label' => 'Service Map',
    'range' => [
        'label' => 'Rentang Waktu',
        '1h' => '1 jam terakhir',
        '24h' => '24 jam terakhir',
        '7d' => '7 hari terakhir',
    ],
    'sections' => [
        'map' => 'Service Map',
        'metrics' => 'Metrik',
    ],
    'edges' => [
        'title' => 'Relasi',
    ],
    'metrics' => [
        'operation' => 'Operasi',
        'requests' => ':count permintaan',
        'error_rate' => 'Error Rate',
        'avg' => 'Rata-rata',
        'p95' => 'P95',
    ],
    'value' => [
        'no_data' => 'Tidak ada data trace dalam rentang waktu terpilih.',
        'no_metrics' => 'Tidak ada metrik untuk rentang waktu terpilih.',
        'generated_at' => 'Data dari :time',
    ],
];