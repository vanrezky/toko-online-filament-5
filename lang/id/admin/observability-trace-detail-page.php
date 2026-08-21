<?php

return [
    'title' => 'Detail Trace Observability',
    'navigation_label' => 'Detail Trace',
    'sections' => [
        'summary' => 'Ringkasan Trace',
        'waterfall' => 'Waterfall',
        'tree' => 'Pohon Span',
    ],
    'summary' => [
        'operation' => 'Operasi Akar',
        'trace_id' => 'Trace ID',
        'correlation_id' => 'Correlation ID',
        'duration' => 'Durasi',
        'status' => 'Status',
        'spans' => 'Span',
        'errors' => 'Error',
        'start' => 'Mulai',
    ],
    'waterfall' => [
        'operation' => 'Operasi',
        'timeline' => 'Linimasa',
    ],
    'span' => [
        'trace_id' => 'Trace ID',
        'span_id' => 'Span ID',
        'parent_span_id' => 'Parent Span ID',
        'operation' => 'Operasi',
        'status' => 'Status',
        'start' => 'Mulai',
        'end' => 'Selesai',
        'correlation_id' => 'Correlation ID',
        'integration_log' => 'Integration Log',
        'status_description' => 'Deskripsi Status',
        'attributes' => 'Atribut',
        'events' => 'Event',
    ],
    'badge' => [
        'slow' => 'Lambat',
    ],
    'actions' => [
        'copy' => 'Salin',
        'integration_logs' => 'Lihat Integration Log terkait',
        'audit_logs' => 'Lihat Audit Log terkait',
        'view_record' => 'Lihat',
    ],
    'value' => [
        'not_found' => 'Trace tidak ditemukan.',
    ],
];