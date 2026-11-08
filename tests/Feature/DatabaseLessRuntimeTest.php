<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

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

    public function test_regular_pages_do_not_query_the_database(): void
    {
        config()->set('showcase.users_demo', false);

        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->get('/')->assertOk();
        $this->get('/pdf')->assertOk();

        $this->assertSame([], $queries);
    }
}
