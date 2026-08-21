<?php

return [
    'title' => 'Cache Management',
    'navigation_label' => 'Cache Management',
    'actions' => [
        'clear' => 'Clear Application Cache',
        'clear_group' => 'Clear :group',
    ],
    'confirmation' => [
        'heading' => 'Clear application cache?',
        'description' => 'Only cache groups created by this application will be invalidated. Queue, Horizon, session, and other Redis data will not be cleared.',
        'group_heading' => 'Clear :group cache?',
        'group_description' => 'Only the selected application cache group will be invalidated. Other cache groups and runtime data will remain untouched.',
        'submit' => 'Clear cache',
        'cancel' => 'Cancel',
    ],
    'overview' => ['heading' => 'Application Cache Overview'],
    'metadata' => [
        'store' => 'Cache Store',
        'default_store' => 'Default Store',
        'prefix' => 'Prefix',
        'connection' => 'Connection',
        'status' => 'Status',
    ],
    'status' => ['connected' => 'Connected', 'unavailable' => 'Unavailable'],
    'managed' => [
        'heading' => 'Managed Application Cache',
        'description' => 'These application cache groups can be safely invalidated. Redis keys and values are not browsable from this page.',
    ],
    'messages' => [
        'unavailable' => 'The application cache could not be checked.',
        'probe_failed' => 'The application cache probe did not return the expected result.',
    ],
    'notifications' => [
        'cleared' => 'Application cache was cleared safely.',
        'group_cleared' => ':group cache was cleared safely.',
        'partial_failure' => 'Application cache was partially cleared. Check the audit log for the affected groups.',
    ],
];
