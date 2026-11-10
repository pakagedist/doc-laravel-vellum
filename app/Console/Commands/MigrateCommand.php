<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Sustituye a los comandos de migraciones del framework.
 *
 * La aplicación es de documentación y no tiene base de datos: los comandos
 * originales intentarían conectar y fallarían. Esta versión acepta las mismas
 * opciones, no toca ningún disco y termina con éxito, de modo que un hook de
 * despliegue que ejecute `php artisan migrate --force` no rompe la aplicación.
 */
class MigrateCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'migrate
                {--database= : The database connection to use}
                {--force : Force the operation to run when in production}
                {--path=* : The path(s) to the migrations files to be executed}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
                {--schema-path= : The path to a schema dump file}
                {--pretend : Dump the SQL queries that would be run}
                {--seed : Indicates if the seed task should be re-run}
                {--seeder= : The class name of the root seeder}
                {--step : Force the migrations to be run so they can be rolled back individually}
                {--graceful : Return a successful exit code even if an error occurs}
                {--drop-views : Drop all tables and views}
                {--drop-types : Drop all tables and types (Postgres only)}
                {--pending= : Only list pending migrations}';

    /**
     * @var list<string>
     */
    protected $aliases = [
        'migrate:fresh',
        'migrate:install',
        'migrate:refresh',
        'migrate:reset',
        'migrate:rollback',
        'migrate:status',
    ];

    /**
     * @var string
     */
    protected $description = 'Run the database migrations (no-op: this application has no database)';

    public function handle(): int
    {
        $this->components->info('Esta aplicación no usa base de datos: no hay nada que migrar.');

        return self::SUCCESS;
    }
}
