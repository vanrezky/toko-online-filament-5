<?php

return [
    'title' => 'Observability Overview',
    'navigation_label' => 'Overview',
    'cluster' => ['navigation_label' => 'Observability'],
    'filters' => ['range' => 'Time range', 'from' => 'From', 'until' => 'Until'],
    'ranges' => ['24h' => 'Last 24 hours', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'custom' => 'Custom range'],
    'cards' => [
        'total_calls' => 'Integration Calls',
        'failed_calls' => 'Failed',
        'avg_duration' => 'Avg Duration',
        'slowest' => 'Slowest',
        'queue_failed' => 'Queue Failed',
        'queue_processed' => 'Queue Processed',
        'health' => 'Health',
    ],
    'value' => [
        'unavailable' => 'Unavailable',
        'duration' => ':ms ms',
        'no_data' => 'No integration activity in the selected range.',
        'no_slow_calls' => 'No integration calls with a measured duration in the selected range.',
        'no_errors' => 'No integration failures in the selected range.',
    ],
    'sections' => [
        'provider_performance' => 'Provider Performance',
        'slow_calls' => 'Slowest Integration Calls',
        'recent_errors' => 'Recent Integration Errors',
    ],
    'provider' => ['provider' => 'Provider', 'calls' => 'Calls', 'failed' => 'Failed', 'failed_rate' => 'Failed Rate', 'avg_duration' => 'Avg Duration'],
    'slow_calls' => ['provider' => 'Provider', 'endpoint' => 'Endpoint', 'duration' => 'Duration', 'status' => 'Status', 'correlation_id' => 'Correlation ID'],
    'recent_errors' => ['time' => 'Time', 'provider' => 'Provider', 'endpoint' => 'Endpoint', 'error' => 'Error', 'correlation_id' => 'Correlation ID'],
    'status' => ['pending' => 'Pending', 'success' => 'Success', 'failed' => 'Failed'],
];