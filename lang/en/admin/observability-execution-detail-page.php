<?php

return [
    'title' => 'Execution Detail',
    'navigation_label' => 'Execution Detail',
    'sections' => [
        'summary' => 'Execution Summary',
        'timeline' => 'Timeline',
    ],
    'summary' => [
        'correlation_id' => 'Correlation ID',
        'integration_events' => 'Integration Events',
        'audit_events' => 'Audit Events',
        'queue_events' => 'Queue Events',
    ],
    'sources' => [
        'integration' => 'Integration',
        'audit' => 'Audit',
        'queue' => 'Queue',
    ],
    'actions' => [
        'view_record' => 'View record',
    ],
    'value' => [
        'no_events' => 'No correlated events were recorded for this execution.',
    ],
];
