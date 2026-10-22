<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PdfDemoController;
use App\Http\Controllers\ShowcaseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la aplicación
|--------------------------------------------------------------------------
|
| La portada (`/`) muestra el catálogo completo del panel de PDF. El panel
| técnico vive bajo el prefijo `/pdf`, con una ruta por categoría, por
| demostración y por variante. Además conviven los documentos que genera la
| propia aplicación (`/pdf/users` y `/pdf/inspect`).
|
*/

Route::get('/', [ShowcaseController::class, 'home'])->name('showcase.home');

Route::get('/documents/users', [DocumentController::class, 'users'])->name('documents.users');

Route::prefix('pdf')->group(function (): void {
    Route::get('/', [ShowcaseController::class, 'index'])->name('pdf.index');

    /*
     | Documentos de la propia aplicación. Se declaran antes que las rutas con
     | parámetros para que `{category}` no se los quede.
     */

    Route::get('users', [PdfDemoController::class, 'users'])->name('pdf.users');

    Route::get('inspect', [PdfDemoController::class, 'inspect'])->name('pdf.inspect');

    Route::get('archivo/{category}/{demo}/{variant}.pdf', [ShowcaseController::class, 'preview'])
        ->name('pdf.preview');

    Route::get('descarga/{category}/{demo}/{variant}', [ShowcaseController::class, 'download'])
        ->name('pdf.download');

    Route::get('json/{category}/{demo}/{variant}', [ShowcaseController::class, 'json'])
        ->name('pdf.showcase.json');

    Route::get('{category}', [ShowcaseController::class, 'category'])->name('pdf.category');

    Route::get('{category}/{demo}', [ShowcaseController::class, 'demo'])->name('pdf.demo');

    Route::get('{category}/{demo}/{variant}', [ShowcaseController::class, 'demo'])->name('pdf.demo.variant');
});
