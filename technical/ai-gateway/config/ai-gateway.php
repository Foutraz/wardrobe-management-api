<?php

return [
    'cutout' => [
        'driver' => env('AI_CUTOUT_DRIVER', 'none'),

        'rembg' => [
            'binary' => env('AI_REMBG_BINARY', 'rembg'),
            'timeout' => (int) env('AI_REMBG_TIMEOUT', 120),
        ],
    ],

    'care_label' => [
        'driver' => env('AI_CARE_LABEL_DRIVER', 'none'),

        'tesseract' => [
            'binary' => env('AI_TESSERACT_BINARY', 'tesseract'),
            'timeout' => (int) env('AI_TESSERACT_TIMEOUT', 60),
        ],
    ],
];
