<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenApi\Generator;

class SwaggerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Sobrescribir la URL del servidor en la documentación generada
        $this->app->afterResolving('l5-swagger.docs', function ($docs) {
            foreach ($docs->getServers() as $server) {
                if ($server->url === 'http://app.appnet.dev') {
                    $server->url = config('app.url');
                }
            }
        });
    }
}
