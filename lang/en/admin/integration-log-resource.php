<?php

return [
    'navigation_label' => 'Integration Logs',
    'model_label' => 'Integration Log',
    'plural_model_label' => 'Integration Logs',
    'columns' => ['timestamp' => 'Timestamp', 'direction' => 'Direction', 'provider' => 'Provider', 'method' => 'Method', 'endpoint' => 'Endpoint', 'status' => 'Status', 'http_status' => 'HTTP Status', 'duration' => 'Duration', 'subject' => 'Subject', 'correlation_id' => 'Correlation ID'],
    'filters' => ['direction' => 'Direction', 'provider' => 'Provider', 'status' => 'Status', 'http_status_from' => 'HTTP from', 'http_status_until' => 'HTTP until', 'from' => 'From date', 'until' => 'Until date', 'has_error' => 'Has error', 'search' => 'Search endpoint, correlation ID, subject, or error'],
    'sections' => ['summary' => 'Summary', 'request' => 'Request', 'response' => 'Response', 'error' => 'Error'],
    'entries' => ['provider' => 'Provider', 'direction' => 'Direction', 'method' => 'Method', 'endpoint' => 'Endpoint', 'status' => 'Status', 'http_status' => 'HTTP Status', 'duration' => 'Duration', 'correlation_id' => 'Correlation ID', 'subject' => 'Triggered by', 'started_at' => 'Started at', 'finished_at' => 'Finished at', 'headers' => 'Headers', 'body' => 'Body', 'error_class' => 'Error class', 'error_message' => 'Error message'],
    'values' => ['inbound' => 'Inbound', 'outbound' => 'Outbound', 'pending' => 'Pending', 'success' => 'Success', 'failed' => 'Failed'],
    'pages' => ['index' => ['title' => 'Integration Logs'], 'view' => ['title' => 'Integration Log Detail']],
];
