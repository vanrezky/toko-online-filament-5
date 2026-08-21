<?php

return [
    'title' => 'Observability Trace Detail',
    'navigation_label' => 'Trace Detail',
    'sections' => [
        'summary' => 'Trace Summary',
        'waterfall' => 'Waterfall',
        'tree' => 'Span Tree',
    ],
    'summary' => [
        'operation' => 'Root Operation',
        'trace_id' => 'Trace ID',
        'correlation_id' => 'Correlation ID',
        'duration' => 'Duration',
        'status' => 'Status',
        'spans' => 'Spans',
        'errors' => 'Errors',
        'start' => 'Started At',
    ],
    'waterfall' => [
        'operation' => 'Operation',
        'timeline' => 'Timeline',
    ],
    'span' => [
        'trace_id' => 'Trace ID',
        'span_id' => 'Span ID',
        'parent_span_id' => 'Parent Span ID',
        'operation' => 'Operation',
        'status' => 'Status',
        'start' => 'Started At',
        'end' => 'Ended At',
        'correlation_id' => 'Correlation ID',
        'integration_log' => 'Integration Log',
        'status_description' => 'Status Description',
        'attributes' => 'Attributes',
        'events' => 'Events',
    ],
    'badge' => [
        'slow' => 'Slow',
    ],
    'actions' => [
        'copy' => 'Copy',
        'integration_logs' => 'View related Integration Logs',
        'audit_logs' => 'View related Audit Logs',
        'view_record' => 'View',
    ],
    'value' => [
        'not_found' => 'Trace not found.',
    ],
];