<?php

namespace App\Providers;

use App\Showcase\ShowcaseCatalog;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // El catálogo del panel se construye una sola vez: las categorías
        // generan sus demostraciones al construirse.
        $this->app->singleton(ShowcaseCatalog::class);

        $this->useDatabaseLessRuntime();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Esta aplicación es de documentación y no necesita base de datos: si el
     * entorno apunta a drivers respaldados por BD (sesiones, caché y cola),
     * se degradan a file/file/sync para no depender de ninguna conexión.
     */
    private function useDatabaseLessRuntime(): void
    {
        if (config('session.driver') === 'database') {
            config(['session.driver' => 'file']);
        }

        if (config('cache.default') === 'database') {
            config(['cache.default' => 'file']);
        }

        if (config('queue.default') === 'database') {
            config(['queue.default' => 'sync']);
        }
    }
}
