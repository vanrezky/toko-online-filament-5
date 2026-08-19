<?php

return [
    'title' => 'Queue Monitor',
    'navigation_label' => 'Queue Monitor',
    'actions' => ['open_horizon' => 'Open Horizon'],
    'cards' => [
        'horizon' => 'Queue Health',
        'pending' => 'Pending',
        'failed' => 'Failed',
        'processed' => 'Processed',
        'queues' => 'Active Queues',
        'workload' => 'Queue Workload',
        'recent_failed' => 'Recent Failed Jobs',
    ],
    'status' => [
        'running' => 'Running',
        'offline' => 'Offline',
        'unavailable' => 'Unavailable',
        'no_workers' => 'No workers',
    ],
    'value' => [
        'unavailable' => 'Unavailable',
        'no_queues' => 'No active queues',
        'no_workload' => 'No workload',
        'no_failed' => 'No failed jobs',
    ],
    'workload' => [
        'queue' => 'Queue',
        'pending' => 'Pending',
        'wait' => 'Wait time',
        'processes' => 'Processes',
        'seconds' => ':count second|:count seconds',
    ],
    'recent_failed' => [
        'job' => 'Job',
        'correlation_id' => 'Correlation ID',
        'failed_at' => 'Failed At',
    ],
];
