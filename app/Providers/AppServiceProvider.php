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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
