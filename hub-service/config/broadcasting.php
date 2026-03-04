<?php

return [
    // allow switching between log, null, pusher, or soketi via env
    'default' => env('BROADCAST_CONNECTION', env('BROADCAST_DRIVER', 'log')),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => env('PUSHER_APP_USE_TLS', true),
            ],
        ],

        // soketi is a self-hosted Pusher-compatible server; we use the pusher driver
        'soketi' => [
            'driver' => 'pusher',
            'key' => env('SOKETI_APP_KEY', env('PUSHER_APP_KEY')),
            'secret' => env('SOKETI_APP_SECRET', env('PUSHER_APP_SECRET')),
            'app_id' => env('SOKETI_APP_ID', env('PUSHER_APP_ID')),
            'options' => [
                'host' => env('SOKETI_HOST', '127.0.0.1'),
                'port' => env('SOKETI_PORT', 6001),
                'scheme' => env('SOKETI_SCHEME', 'http'),
                'encrypted' => env('SOKETI_ENCRYPTED', false),
                'useTLS' => env('SOKETI_ENCRYPTED', false),
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],
];
