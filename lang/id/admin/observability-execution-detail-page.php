<?php

return [
    'title' => 'Detail Eksekusi',
    'navigation_label' => 'Detail Eksekusi',
    'sections' => [
        'summary' => 'Ringkasan Eksekusi',
        'timeline' => 'Linimasa',
    ],
    'summary' => [
        'correlation_id' => 'Correlation ID',
        'integration_events' => 'Event Integrasi',
        'audit_events' => 'Event Audit',
        'queue_events' => 'Event Antrean',
    ],
    'sources' => [
        'integration' => 'Integrasi',
        'audit' => 'Audit',
        'queue' => 'Antrean',
    ],
    'actions' => [
        'view_record' => 'Lihat catatan',
    ],
    'value' => [
        'no_events' => 'Tidak ada event berkorelasi yang tercatat untuk eksekusi ini.',
    ],
];
