<?php

return [
    'cutout' => [
        'driver' => env('AI_CUTOUT_DRIVER', 'none'),

        'rembg' => [
            'binary' => env('AI_REMBG_BINARY', 'rembg'),
            'timeout' => (int) env('AI_REMBG_TIMEOUT', 120),
        ],
    ],
];
