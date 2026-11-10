<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Throwable;

/**
 * La aplicación es de documentación y no necesita base de datos: los drivers
 * respaldados por BD deben degradarse solos y las páginas normales no deben
 * abrir ninguna conexión.
 */
class DatabaseLessRuntimeTest extends TestCase
{
    public function test_database_backed_drivers_are_replaced_by_local_ones(): void
    {
        config([
            'session.driver' => 'database',
            'cache.default' => 'database',
            'queue.default' => 'database',
        ]);

        (new AppServiceProvider($this->app))->register();

        $this->assertSame('file', config('session.driver'));
        $this->assertSame('file', config('cache.default'));
        $this->assertSame('sync', config('queue.default'));
    }

    public function test_no_database_connection_is_configured(): void
    {
        $this->assertNull(config('database.default'));
        $this->assertSame([], config('database.connections'));

        $this->expectException(Throwable::class);

        DB::connection();
    }

    public function test_the_migrate_command_finishes_without_touching_a_database(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $this->assertFileDoesNotExist(database_path('database.sqlite'));
    }

    public function test_regular_pages_do_not_query_the_database(): void
    {
        $queries = [];

        Event::listen(QueryExecuted::class, function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->get('/')->assertOk();
        $this->get('/pdf')->assertOk();

        $this->assertSame([], $queries);
    }
}
