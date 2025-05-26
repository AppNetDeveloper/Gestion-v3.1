<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Ollama Tasker API',
                'description' => 'Documentación de la API de Ollama Tasker',
                'version' => '1.0.0',
                'contact' => [
                    'email' => 'info@appnetdeveloper.com'
                ],
            ],
            'routes' => [
                'api' => 'api/documentation',
                'docs' => 'docs',
                'oauth2_callback' => 'api/oauth2-callback',
                'middleware' => [
                    'api' => [],
                    'asset' => [],
                    'docs' => [],
                    'oauth2_callback' => [],
                ],
            ],
            'paths' => [
                'docs' => storage_path('api-docs'),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'views' => base_path('resources/views/vendor/l5-swagger'),
                'base' => env('L5_SWAGGER_BASE_PATH', null),
                'excludes' => [],
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
                'annotations' => [
                    base_path('app/Http/Controllers/Api'),
                    base_path('app/Http/Controllers'),
                ],
                'paths' => [
                    base_path('app/Http/Controllers/Api/WhatsappMessageController.php'),
                    base_path('app/Http/Controllers/Api/TelegramController.php'),
                    base_path('app/Http/Controllers/Api/ScrapingCallbackController.php'),
                    base_path('app/Http/Controllers/Api/ServerMonitorController.php'),
                    base_path('app/Http/Controllers/Api/SwaggerController.php'),
                ],
            ],
            'scanOptions' => [
                'exclude' => [
                    base_path('vendor'),
                    base_path('storage'),
                    base_path('bootstrap/cache'),
                ],
            ],
            'additional_config_url' => null,
            'validator_url' => null,
            'operations_sort' => null,
            'proxy' => env('TRUSTED_PROXY', false),
        ],
    ],
];
