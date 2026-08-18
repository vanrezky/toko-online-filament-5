<?php

return [
    'title' => 'System Health',
    'navigation_label' => 'System Health',
    'actions' => ['refresh' => 'Refresh Health'],
    'overall' => ['heading' => 'Overall System Status'],
    'status' => ['healthy' => 'Healthy', 'warning' => 'Warning', 'failed' => 'Failed', 'unknown' => 'Unknown'],
    'checks' => ['application' => 'Application', 'database' => 'Database', 'redis' => 'Redis', 'queue' => 'Queue', 'disk' => 'Disk'],
    'meta' => ['environment' => 'Environment', 'laravel_version' => 'Laravel', 'php_version' => 'PHP', 'connection_name' => 'Connection', 'disk_space_used_percentage' => 'Disk usage'],
    'messages' => ['unavailable' => 'Health results are unavailable or cannot be read.'],
    'notifications' => ['refreshed' => 'Health check was run successfully.'],
];
