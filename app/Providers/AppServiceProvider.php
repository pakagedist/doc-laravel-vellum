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
        $this->preparePdfTemporaryFolder();
    }

    /**
     * mPDF necesita un directorio escribible para sus temporales y su caché
     * de fuentes: por defecto apunta dentro de `vendor/`, que en producción no
     * se puede escribir.
     */
    private function preparePdfTemporaryFolder(): void
    {
        $folder = config('pdf.drivers.mpdf.temp_folder');

        if (! is_string($folder) || $folder === '') {
            return;
        }

        if (! is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }
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

        // Laravel fusiona la configuración de base de datos del framework con
        // la de la aplicación, por lo que mysql/pgsql/sqlite vuelven a existir
        // aunque la app declare la lista de conexiones vacía. Se vacían aquí
        // para que cualquier intento de conexión falle de forma explícita en
        // lugar de crear un archivo SQLite en el servidor.
        config([
            'database.default' => null,
            'database.connections' => [],
        ]);
    }
}
