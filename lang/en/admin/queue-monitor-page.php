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
    ],
    'workload' => [
        'queue' => 'Queue',
        'pending' => 'Pending',
        'wait' => 'Wait time',
        'processes' => 'Processes',
        'seconds' => ':count second|:count seconds',
    ],
];
