<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Documents\EngineProbe;
use App\Showcase\ShowcaseCatalog;
use App\Showcase\ShowcaseCategory;
use App\Showcase\ShowcaseDemo;
use App\Showcase\ShowcaseRunner;
use App\Showcase\ShowcaseVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel de demostración de la librería de PDF.
 *
 * Cada pantalla ejecuta una parte de la API y enseña el resultado: el PDF
 * generado, las métricas del render y el código que lo produce.
 */
final class ShowcaseController extends Controller
{
    public function __construct(
        private readonly ShowcaseCatalog $catalog,
        private readonly ShowcaseRunner $runner,
        private readonly EngineProbe $probe,
    ) {}

    /**
     * Portada de la aplicación: qué hay y dónde se puede ver.
     */
    public function home(): Response
    {
        return response()->view('showcase.home', [
            'categories' => $this->catalog->categories(),
            'engines' => $this->probe->probeAll(),
            'defaultDriver' => (string) config('pdf.default_driver'),
            'totalDemos' => $this->catalog->totalDemos(),
            'routes' => $this->applicationRoutes(),
        ]);
    }

    /**
     * Índice técnico del panel.
     */
    public function index(): Response
    {
        return response()->view('showcase.index', [
            'categories' => $this->catalog->categories(),
            'engines' => $this->probe->probeAll(),
            'defaultDriver' => (string) config('pdf.default_driver'),
        ]);
    }

    /**
     * Las demostraciones de una categoría.
     */
    public function category(string $category): Response
    {
        $categoria = $this->catalog->category($category);

        if ($categoria === null) {
            abort(404, 'La categoría «'.$category.'» no existe.');
        }

        return response()->view('showcase.category', [
            'category' => $categoria,
            'categories' => $this->catalog->categories(),
        ]);
    }

    /**
     * Una demostración, en la variante pedida.
     */
    public function demo(string $category, string $demo, ?string $variant = null): Response
    {
        $categoria = $this->catalog->category($category);
        $demostracion = $categoria?->demo($demo);

        if ($categoria === null || $demostracion === null) {
            abort(404, 'La demostración «'.$category.'/'.$demo.'» no existe.');
        }

        $variante = $demostracion->variant($variant ?? $demostracion->defaultVariant()->key);
        $resultado = $this->runner->run($demostracion, $variante);

        if ($resultado->bytes !== null) {
            $this->storePreview($categoria, $demostracion, $variante, $resultado->bytes);
        }

        return response()->view('showcase.demo', [
            'category' => $categoria,
            'demo' => $demostracion,
            'variant' => $variante,
            'outcome' => $resultado,
            'categories' => $this->catalog->categories(),
        ]);
    }

    /**
     * El PDF de una demostración, servido en línea.
     */
    public function preview(string $category, string $demo, string $variant): Response
    {
        [$bytes, $nombre] = $this->previewBytes($category, $demo, $variant);

        if ($bytes === null) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'No se ha podido generar el PDF de esta demostración.');
        }

        return response($bytes, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$nombre.'"',
            'Content-Length' => (string) strlen($bytes),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * El PDF de una demostración, como descarga.
     */
    public function download(string $category, string $demo, string $variant): Response
    {
        [$bytes, $nombre] = $this->previewBytes($category, $demo, $variant, true);

        if ($bytes === null) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'No se ha podido generar el PDF de esta demostración.');
        }

        return response($bytes, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
            'Content-Length' => (string) strlen($bytes),
        ]);
    }

    /**
     * El PDF de una demostración, en JSON con el contenido en base64.
     */
    public function json(string $category, string $demo, string $variant): JsonResponse
    {
        [$bytes] = $this->previewBytes($category, $demo, $variant, true);

        if ($bytes === null) {
            return response()->json(['error' => 'No se ha podido generar el PDF.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'file' => base64_encode($bytes),
            'size' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
        ]);
    }

    /**
     * Bytes del PDF de una demostración, reutilizando la vista previa si existe.
     *
     * @return array{0: string|null, 1: string}
     */
    private function previewBytes(string $category, string $demo, string $variant, bool $refrescar = false): array
    {
        $categoria = $this->catalog->category($category);
        $demostracion = $categoria?->demo($demo);

        if ($categoria === null || $demostracion === null) {
            abort(404, 'La demostración «'.$category.'/'.$demo.'» no existe.');
        }

        $variante = $demostracion->variant($variant);
        $ruta = $this->previewPath($categoria, $demostracion, $variante);
        $nombre = $demostracion->slug.'-'.$variante->key.'.pdf';

        if (! $refrescar && is_file($ruta)) {
            return [(string) file_get_contents($ruta), $nombre];
        }

        $resultado = $this->runner->run($demostracion, $variante);

        if ($resultado->bytes === null) {
            return [null, $nombre];
        }

        $this->storePreview($categoria, $demostracion, $variante, $resultado->bytes);

        return [$resultado->bytes, $nombre];
    }

    /**
     * Escribe la vista previa del PDF en disco.
     */
    private function storePreview(
        ShowcaseCategory $category,
        ShowcaseDemo $demo,
        ShowcaseVariant $variant,
        string $bytes,
    ): void {
        $directorio = storage_path('app/pdf-showcase');

        if (! is_dir($directorio)) {
            mkdir($directorio, 0o755, true);
        }

        file_put_contents($this->previewPath($category, $demo, $variant), $bytes);
    }

    /**
     * Ruta del archivo de vista previa de una demostración.
     */
    private function previewPath(ShowcaseCategory $category, ShowcaseDemo $demo, ShowcaseVariant $variant): string
    {
        $clave = md5($category->slug.'|'.$demo->slug.'|'.$variant->key);

        return storage_path('app/pdf-showcase/preview-'.$clave.'.pdf');
    }

    /**
     * Rutas de la aplicación, para el apartado «qué hay aquí».
     *
     * @return list<array{method: string, uri: string, name: string}>
     */
    private function applicationRoutes(): array
    {
        $rutas = [];

        foreach (Route::getRoutes()->getRoutes() as $ruta) {
            if (str_starts_with($ruta->uri(), 'vendor') || str_contains($ruta->uri(), '{')) {
                continue;
            }

            $rutas[] = [
                'method' => implode('|', $ruta->methods()),
                'uri' => '/'.ltrim($ruta->uri(), '/'),
                'name' => $ruta->getName() ?? '',
            ];
        }

        usort($rutas, static fn (array $a, array $b): int => strcmp($a['uri'], $b['uri']));

        return $rutas;
    }
}
