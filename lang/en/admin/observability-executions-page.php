<?php

return [
    'title' => 'Observability Executions',
    'navigation_label' => 'Executions',
    'search' => [
        'label' => 'Correlation ID',
        'placeholder' => 'Search by correlation ID…',
    ],
    'sections' => [
        'executions' => 'Executions',
    ],
    'executions' => [
        'correlation_id' => 'Correlation ID',
        'last_event' => 'Last Event',
        'open' => 'Open',
    ],
    'value' => [
        'no_executions' => 'No executions match the current search.',
    ],
    'status' => ['pending' => 'Pending', 'success' => 'Success', 'failed' => 'Failed'],
];
