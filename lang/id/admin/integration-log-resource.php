<?php

return [
    'navigation_label' => 'Integration Logs',
    'model_label' => 'Integration Log',
    'plural_model_label' => 'Integration Logs',
    'columns' => ['timestamp' => 'Waktu', 'direction' => 'Arah', 'provider' => 'Provider', 'method' => 'Metode', 'endpoint' => 'Endpoint', 'status' => 'Status', 'http_status' => 'Status HTTP', 'duration' => 'Durasi', 'subject' => 'Subjek', 'correlation_id' => 'Correlation ID'],
    'filters' => ['direction' => 'Arah', 'provider' => 'Provider', 'status' => 'Status', 'http_status_from' => 'HTTP dari', 'http_status_until' => 'HTTP sampai', 'from' => 'Dari tanggal', 'until' => 'Sampai tanggal', 'has_error' => 'Memiliki error', 'search' => 'Cari endpoint, correlation ID, subjek, atau error'],
    'sections' => ['summary' => 'Ringkasan', 'request' => 'Request', 'response' => 'Response', 'error' => 'Error'],
    'entries' => ['provider' => 'Provider', 'direction' => 'Arah', 'method' => 'Metode', 'endpoint' => 'Endpoint', 'status' => 'Status', 'http_status' => 'Status HTTP', 'duration' => 'Durasi', 'correlation_id' => 'Correlation ID', 'subject' => 'Dipicu oleh', 'started_at' => 'Dimulai', 'finished_at' => 'Selesai', 'headers' => 'Headers', 'body' => 'Body', 'error_class' => 'Kelas error', 'error_message' => 'Pesan error'],
    'values' => ['inbound' => 'Inbound', 'outbound' => 'Outbound', 'pending' => 'Pending', 'success' => 'Success', 'failed' => 'Failed'],
    'pages' => ['index' => ['title' => 'Integration Logs'], 'view' => ['title' => 'Detail Integration Log']],
];
