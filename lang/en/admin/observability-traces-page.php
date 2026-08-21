<?php

return [
    'title' => 'Observability Traces',
    'navigation_label' => 'Traces',
    'search' => [
        'label' => 'Search',
        'placeholder' => 'Search by trace ID or correlation ID…',
    ],
    'range' => [
        'label' => 'Time Range',
        '1h' => 'Last 1 hour',
        '24h' => 'Last 24 hours',
        '7d' => 'Last 7 days',
        'all' => 'All time',
    ],
    'status' => [
        'label' => 'Status',
        'all' => 'Any status',
        'UNSET' => 'Unset',
        'OK' => 'OK',
        'ERROR' => 'Error',
    ],
    'operation' => [
        'label' => 'Root Operation',
        'placeholder' => 'Filter by operation…',
    ],
    'correlation' => [
        'label' => 'Correlation ID',
        'placeholder' => 'Filter by correlation ID…',
    ],
    'duration' => [
        'min_label' => 'Min Duration (ms)',
        'max_label' => 'Max Duration (ms)',
        'min_placeholder' => 'e.g. 500',
        'max_placeholder' => 'e.g. 5000',
    ],
    'errors' => [
        'label' => 'Has errors',
    ],
    'sections' => [
        'traces' => 'Traces',
    ],
    'traces' => [
        'timestamp' => 'Timestamp',
        'trace_id' => 'Trace ID',
        'correlation_id' => 'Correlation ID',
        'operation' => 'Operation',
        'status' => 'Status',
        'duration' => 'Duration',
        'spans' => 'Spans',
        'errors' => 'Errors',
        'open' => 'Open',
    ],
    'badge' => [
        'partial' => 'Partial',
    ],
    'pagination' => [
        'total' => ':total traces',
        'prev' => 'Prev',
        'next' => 'Next',
    ],
    'value' => [
        'no_traces' => 'No traces match the current filters.',
    ],
];