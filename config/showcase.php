<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | URL de la demostración de fuente remota
    |--------------------------------------------------------------------------
    |
    | La demostración «Fuente por URL» necesita una página web que Vellum pueda
    | descargar con cURL. Por defecto usa el archivo estático que la propia
    | aplicación publica en `/showcase/remoto.html`: al ser un archivo del
    | servidor web, no necesita pasar por PHP y no se bloquea a sí misma.
    |
    | Si la aplicación se sirve en un dominio que el servidor no puede
    | alcanzar, se puede apuntar a cualquier otra URL con la variable de
    | entorno `SHOWCASE_REMOTE_URL`.
    |
    | En las pruebas se sustituye por un servidor HTTP local.
    |
    */

    'remote_url' => env('SHOWCASE_REMOTE_URL'),

    /*
    |--------------------------------------------------------------------------
    | URL de la variante de error
    |--------------------------------------------------------------------------
    |
    | La variante «error» de la fuente por URL apunta a un puerto que no
    | escucha, para que la librería falle de inmediato al conectar.
    |
    */

    'remote_error_url' => env('SHOWCASE_REMOTE_ERROR_URL', 'http://127.0.0.1:1/vellum-no-existe.html'),

    /*
    |--------------------------------------------------------------------------
    | Vistas previas
    |--------------------------------------------------------------------------
    |
    | Los PDF generados por el panel se guardan en disco para poder servirlos
    | sin volver a renderizarlos en cada visita.
    |
    */

    'preview_directory' => 'pdf-showcase',

    /*
    |--------------------------------------------------------------------------
    | Demos con base de datos
    |--------------------------------------------------------------------------
    |
    | `/documents/users` y `/pdf/users` pueden consultar la tabla `users`.
    | Por defecto no tocan la base de datos y usan datos de ejemplo, para que
    | la aplicación de documentación arranque sin BD. Actívalas con
    | SHOWCASE_USERS_DEMO=true y ejecutando `php artisan migrate --force --seed`.
    |
    */

    'users_demo' => (bool) env('SHOWCASE_USERS_DEMO', false),

];
