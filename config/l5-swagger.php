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
                    'email' => 'info@appnet.dev'
                ],
            ],
            'servers' => [
                [
                    'url' => config('app.url'),
                    'description' => 'Servidor de la API',
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
            'security' => [
                ['bearerAuth' => []],
                ['whatsappToken' => []],
                ['telegramToken' => []]
            ],
            'securityDefinitions' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                    'name' => 'Authorization',
                    'in' => 'header',
                    'description' => 'Ingresa el token de autenticación JWT con el prefijo Bearer',
                ],
                'whatsappToken' => [
                    'type' => 'apiKey',
                    'name' => 'Authorization',
                    'in' => 'header',
                    'description' => 'Ingresa el token de la API de WhatsApp',
                ],
                'telegramToken' => [
                    'type' => 'apiKey',
                    'name' => 'Authorization',
                    'in' => 'header',
                    'description' => 'Ingresa el token de la API de Telegram',
                ]
            ],
            'paths' => [
                'docs' => storage_path('api-docs'),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'views' => base_path('resources/views/vendor/l5-swagger'),
                'base' => env('L5_SWAGGER_BASE_PATH', null),
                'excludes' => [],
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
                'swagger' => '3.0',
                'openapi' => '3.0.0',
                'security' => [
                    ['bearerAuth' => []]
                ],
                'annotations' => [
                    base_path('app/Http/Controllers/Api'),
                    base_path('app/Http/Controllers'),
                ],
                'paths' => [
                    base_path('app/Http/Controllers/Api/WhatsappMessageController.php'),
                    base_path('app/Http/Controllers/Api/WhatsAppProxyController.php'),
                    base_path('app/Http/Controllers/Api/TelegramController.php'),
                    base_path('app/Http/Controllers/Api/TelegramProxyController.php'),
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
