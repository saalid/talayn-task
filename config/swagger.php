<?php

return [
    'title' => 'Gold Market API',
    'description' => 'API for handling Gold Buy and Sell orders.',
    'version' => '1.0.0',
    'openapi' => '3.0.0',
    'output' => storage_path('api-docs/swagger.json'),
    'scan' => [
        app_path('Http/Controllers'),
    ]
];
