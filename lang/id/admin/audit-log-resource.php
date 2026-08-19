<?php

return [
    'navigation_label' => 'Audit Logs',
    'model_label' => 'Audit Log',
    'plural_model_label' => 'Audit Logs',
    'system' => 'System',
    'columns' => ['timestamp' => 'Waktu', 'actor' => 'Aktor', 'action' => 'Aksi', 'subject_type' => 'Tipe Subjek', 'subject' => 'Subjek', 'description' => 'Deskripsi'],
    'filters' => ['actor' => 'Aktor', 'action' => 'Aksi', 'subject_type' => 'Tipe Subjek', 'from' => 'Dari tanggal', 'until' => 'Sampai tanggal', 'correlation_id' => 'Correlation ID'],
    'sections' => ['activity' => 'Aktivitas', 'metadata' => 'Metadata Request', 'changes' => 'Perubahan'],
    'entries' => ['actor' => 'Aktor', 'action' => 'Aksi', 'subject' => 'Subjek', 'timestamp' => 'Waktu', 'description' => 'Deskripsi', 'correlation_id' => 'Correlation ID', 'ip' => 'IP', 'method' => 'Method', 'url' => 'URL', 'user_agent' => 'User Agent', 'field' => 'Field', 'old' => 'Nilai Lama', 'new' => 'Nilai Baru'],
    'values' => ['true' => 'Ya', 'false' => 'Tidak'],
    'empty_changes' => 'Tidak ada perubahan field yang tersedia.',
    'pages' => ['index' => ['title' => 'Audit Logs'], 'view' => ['title' => 'Detail Audit Log']],
];
