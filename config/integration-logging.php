<?php

return [
    'payload_max_bytes' => (int) env('INTEGRATION_LOG_PAYLOAD_MAX_BYTES', 65536),
    'retention_days' => (int) env('INTEGRATION_LOG_RETENTION_DAYS', 90),
    'sensitive_keys' => [
        'authorization',
        'proxy-authorization',
        'cookie',
        'set-cookie',
        'api_key',
        'apikey',
        'api-key',
        'access_token',
        'refresh_token',
        'token',
        'password',
        'secret',
        'client_secret',
        'private_key',
        'signature_secret',
        'x-api-co-id',
    ],
];
