<?php

return [
    'tokens' => [
        'idle_timeout_minutes' => (int) env('AUTH_IDLE_TIMEOUT_MINUTES', 20),
        'name' => env('AUTH_TOKEN_NAME', 'api'),
    ],
];
