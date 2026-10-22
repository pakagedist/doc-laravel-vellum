<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Documents\DocumentOptions;
use App\Documents\DocumentRenderer;
use App\Documents\PdfInspector;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vellum\Exceptions\DriverNotSupportedException;

/**
 * Documentos que genera la propia aplicación.
 *
 * Cada endpoint ejercita una parte distinta de la API para poder ver de un
 * vistazo qué es capaz de hacer el paquete.
 */
final class PdfDemoController extends Controller
{
    public function __construct(
        private readonly DocumentRenderer $renderer,
    ) {}

    /**
     * El listado de usuarios en PDF, con las opciones que llegan por la query.
     *
     * ?driver=mpdf · ?inline=1 · ?orientation=landscape · ?paper=a5 · ?font=Nimbus%20Sans
     */
    public function users(Request $request): Response
    {
        $options = $this->options($request);

        $data = [
            'users' => User::query()->orderBy('name')->get(),
            'generatedAt' => now(),
        ];

        try {
            if ($request->boolean('inline')) {
                return $this->renderer->inline('documents.users', $data, $options);
            }

            return $this->renderer->download('documents.users', $data, $options);
        } catch (DriverNotSupportedException $exception) {
            return $this->missingDriverResponse($exception, $options->driver);
        }
    }

    /**
     * Métricas del mismo render, en JSON.
     */
    public function inspect(Request $request): JsonResponse
    {
        $options = $this->options($request);

        try {
            $result = $this->renderer->render('documents.users', [
                'users' => User::query()->orderBy('name')->get(),
                'generatedAt' => now(),
            ], $options);
        } catch (DriverNotSupportedException $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
                'driver' => $options->driver,
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'driver' => $result->driver,
            'source' => $result->sourceType,
            'filename' => $result->filename,
            'contentType' => $result->contentType(),
            'size' => $result->size(),
            'durationMs' => round($result->durationMs, 2),
            'pages' => PdfInspector::pageCount($result->bytes),
            'mediaBox' => PdfInspector::mediaBox($result->bytes),
            'sha256' => $result->sha256(),
            'warnings' => $result->warnings,
        ]);
    }

    /**
     * Explica que el motor pedido no está instalado, en lugar de fallar.
     */
    private function missingDriverResponse(DriverNotSupportedException $exception, ?string $driver): Response
    {
        return response()->view('pdf.missing-driver', [
            'driver' => $driver ?? 'desconocido',
            'message' => $exception->getMessage(),
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * Opciones del documento a partir de la query string.
     */
    private function options(Request $request): DocumentOptions
    {
        $driver = $request->string('driver')->trim()->value();
        $font = $request->string('font')->trim()->value();

        return new DocumentOptions(
            driver: $driver !== '' ? $driver : null,
            paper: $request->filled('paper') ? $request->string('paper')->trim()->value() : null,
            orientation: $request->filled('orientation') ? $request->string('orientation')->trim()->value() : null,
            filename: 'usuarios-'.now()->format('Y-m-d').'.pdf',
            metadata: [
                'title' => 'Listado de usuarios',
                'author' => (string) config('app.name'),
                'subject' => 'Usuarios registrados en la aplicación',
            ],
            font: $font !== '' ? $font : null,
        );
    }
}
