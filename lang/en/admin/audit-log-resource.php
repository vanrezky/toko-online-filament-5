<?php

return [
    'navigation_label' => 'Audit Logs',
    'model_label' => 'Audit Log',
    'plural_model_label' => 'Audit Logs',
    'system' => 'System',
    'columns' => ['timestamp' => 'Timestamp', 'actor' => 'Actor', 'action' => 'Action', 'subject_type' => 'Subject Type', 'subject' => 'Subject', 'description' => 'Description'],
    'filters' => ['actor' => 'Actor', 'action' => 'Action', 'subject_type' => 'Subject Type', 'from' => 'From date', 'until' => 'Until date'],
    'sections' => ['activity' => 'Activity', 'metadata' => 'Request Metadata', 'changes' => 'Changes'],
    'entries' => ['actor' => 'Actor', 'action' => 'Action', 'subject' => 'Subject', 'timestamp' => 'Timestamp', 'description' => 'Description', 'ip' => 'IP', 'method' => 'Method', 'url' => 'URL', 'user_agent' => 'User Agent', 'field' => 'Field', 'old' => 'Old Value', 'new' => 'New Value'],
    'values' => ['true' => 'Yes', 'false' => 'No'],
    'empty_changes' => 'No field-level changes are available.',
    'pages' => ['index' => ['title' => 'Audit Logs'], 'view' => ['title' => 'Audit Log Detail']],
];
