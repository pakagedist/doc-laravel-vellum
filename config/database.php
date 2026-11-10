<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Base de datos
|--------------------------------------------------------------------------
|
| Esta aplicación es de documentación y no usa base de datos: no hay
| modelos, migraciones ni seeders. Este archivo existe únicamente para
| sobrescribir la configuración que Laravel 13 fusiona desde el framework
| (`vendor/laravel/framework/config/database.php`): sin él, el framework
| dejaría sqlite apuntando a `database/database.sqlite` y cualquier
| comando de base de datos volvería a tocar el disco.
|
| `default` es `null` a propósito: ninguna conexión puede abrirse por
| defecto, ni siquiera por error. El proveedor de la aplicación vacía
| además `database.connections` en tiempo de ejecución para eliminar las
| conexiones que el framework reinyecta (mysql, pgsql, sqlsrv...).
|
*/

return [

    'default' => null,

    'connections' => [],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

];
