<?php

return [
    'title' => 'Observability Service Map',
    'navigation_label' => 'Service Map',
    'range' => [
        'label' => 'Time Range',
        '1h' => 'Last 1 hour',
        '24h' => 'Last 24 hours',
        '7d' => 'Last 7 days',
    ],
    'sections' => [
        'map' => 'Service Map',
        'metrics' => 'Metrics',
    ],
    'edges' => [
        'title' => 'Relationships',
    ],
    'metrics' => [
        'operation' => 'Operation',
        'requests' => ':count requests',
        'error_rate' => 'Error Rate',
        'avg' => 'Avg',
        'p95' => 'P95',
    ],
    'value' => [
        'no_data' => 'No trace data recorded in the selected range.',
        'no_metrics' => 'No metrics available for the selected range.',
        'generated_at' => 'Data from :time',
    ],
];