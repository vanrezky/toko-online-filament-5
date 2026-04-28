<?php

return [
    'enable_sse' => false,
    'endpoints' => [
        'messages' => [
            'uri' => '/api/streamable',
            'middleware' => [
                'api',
                //\Superconductor\Transports\StreamableHttp\Http\Middleware\ValidateOriginHeader::class,
                //\Superconductor\Transports\StreamableHttp\Http\Middleware\BoundToLocalhost::class,
            ],
        ],
        'stream' => [
            'uri' => '/api/streamable',
            'middleware' => [
                'api',
                \Superconductor\Transports\StreamableHttp\Http\Middleware\CheckIfStreamingEnabled::class,
                //\Superconductor\Transports\StreamableHttp\Http\Middleware\ValidateOriginHeader::class,
                //\Superconductor\Transports\StreamableHttp\Http\Middleware\BoundToLocalhost::class,
            ],
        ],
    ],
];
