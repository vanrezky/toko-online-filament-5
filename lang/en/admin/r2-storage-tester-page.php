<?php

return [
    'title' => 'Cloudflare R2 Tester',
    'navigation_label' => 'R2 Tester',
    'actions' => [
        'test' => 'Test R2 Connection',
    ],
    'section' => [
        'heading' => 'Test Cloudflare R2',
        'description' => 'The test writes, reads, verifies, and removes a temporary object. R2 credentials are loaded from the application environment and are never shown here.',
    ],
    'notifications' => [
        'success' => 'Cloudflare R2 is connected and operational.',
        'failed' => 'Cloudflare R2 connection test failed.',
        'failed_body' => 'Check the R2 environment variables, bucket, endpoint, and access permissions.',
    ],
];
