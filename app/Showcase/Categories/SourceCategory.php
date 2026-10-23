<?php

declare(strict_types=1);

namespace App\Showcase\Categories;

use App\Showcase\ShowcaseCategory;
use App\Showcase\ShowcaseCategoryBuilder;
use App\Showcase\ShowcaseDemo;
use App\Showcase\ShowcaseOutcome;
use App\Showcase\ShowcaseVariant;
use App\Showcase\Sources\PlantillaSource;
use App\Showcase\Support\SampleData;
use Vellum\Exceptions\SourceNotFoundException;
use Vellum\PdfManager;

/**
 * Las seis formas que tiene Vellum de obtener el HTML de un documento.
 */
final class SourceCategory extends ShowcaseCategoryBuilder
{
    public function __construct(private readonly PdfManager $pdf) {}

    public function build(): ShowcaseCategory
    {
        return new ShowcaseCategory(
            slug: 'fuentes',
            title: 'Fuentes de documento',
            summary: 'Vellum no solo acepta HTML: cualquier origen que sepa devolver HTML vale. '
                .'Las seis fuentes todas pasan por el mismo renderizador, así que el motor es intercambiable.',
            demos: [
                $this->htmlDemo(),
                $this->bladeDemo(),
                $this->fileDemo(),
                $this->urlDemo(),
                $this->twigDemo(),
                $this->customDemo(),
            ],
        );
    }

    private function htmlDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // El HTML se pasa tal cual: no hay archivos de por medio.
            $result = Pdf::html(<<<'HTML'
                <h1>Hola desde Vellum</h1>
                <p>Este PDF se ha generado a partir de una cadena HTML.</p>
                HTML)->render();

            $result->bytes;   // El PDF completo
            $result->size();  // Tamaño en bytes
            PHP;

        return $this->demo(
            slug: 'html',
            title: 'HTML en línea',
            summary: 'La forma más directa: una cadena HTML que se convierte en PDF sin tocar el disco.',
            code: $code,
            variants: [
                $this->variant('basico', 'Encabezado y párrafo', 'El mínimo: una etiqueta y un texto.'),
                $this->variant('estilos', 'Con estilos', 'El mismo HTML con CSS embebido.'),
                $this->variant('tabla', 'Con tabla y salto de página', 'Etiquetas de bloque y `page-break` de CSS.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $html = match ($variant->key) {
                    'estilos' => <<<'HTML'
                        <html><head><style>
                            body { font-family: "DejaVu Sans", sans-serif; color: #1f2933; }
                            h1 { color: #0b5cad; border-bottom: 2px solid #0b5cad; padding-bottom: 6px; }
                            .destacado { background: #eef4fb; padding: 12px; border-left: 4px solid #0b5cad; }
                        </style></head><body>
                            <h1>Documento con estilos</h1>
                            <p class="destacado">El CSS va dentro del propio HTML: Vellum entrega la cadena
                            al motor sin transformarla.</p>
                        </body></html>
                        HTML,
                    'tabla' => <<<'HTML'
                        <html><head><style>
                            body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; }
                            table { width: 100%; border-collapse: collapse; }
                            th { background: #1f2933; color: #fff; padding: 6px; text-align: left; }
                            td { border-bottom: 1px solid #ddd; padding: 6px; }
                            .corte { page-break-after: always; }
                        </style></head><body>
                            <h1>Tabla y salto de página</h1>
                            <table>
                                <thead><tr><th>Concepto</th><th>Importe</th></tr></thead>
                                <tbody>
                                    <tr><td>Desarrollo</td><td>1.200,00 €</td></tr>
                                    <tr><td>Mantenimiento</td><td>450,00 €</td></tr>
                                    <tr><td>Formación</td><td>480,00 €</td></tr>
                                </tbody>
                            </table>
                            <p class="corte"></p>
                            <h1>Segunda página</h1>
                            <p>El salto lo provoca <code>page-break-after</code>, no una llamada a la API.</p>
                        </body></html>
                        HTML,
                    default => <<<'HTML'
                        <h1>Hola desde Vellum</h1>
                        <p>Este PDF se ha generado a partir de una cadena HTML.</p>
                        HTML,
                };

                $result = $this->pdf->html($html)->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'La fuente `html` entrega la cadena tal cual al motor.',
                ]);
            },
        );
    }

    private function bladeDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // La vista Blade se renderiza con los datos que se le pasan.
            $result = Pdf::view('documents.invoice', [
                'factura' => $factura,
            ])->render();

            // El motor Blade también se puede forzar:
            // Pdf::from(BladeSource::make('documents.invoice', $data, 'php'));
            PHP;

        return $this->demo(
            slug: 'blade',
            title: 'Vista Blade',
            summary: 'La vía natural en Laravel: una vista Blade con los datos del modelo.',
            code: $code,
            variants: [
                $this->variant('factura', 'Factura', 'Plantilla con importes, IVA y datos fiscales.'),
                $this->variant('listado', 'Listado de usuarios', 'La plantilla que usa la propia aplicación.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                if ($variant->key === 'listado') {
                    $result = $this->pdf->view('documents.informe', [
                        'users' => SampleData::users(),
                        'generatedAt' => now(),
                    ])->render();
                } else {
                    $result = $this->pdf->view('documents.invoice', [
                        'factura' => SampleData::invoice(),
                    ])->render();
                }

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'La plantilla vive en `resources/views/documents/`.',
                    'El papel y la orientación los decide el documento, no el CSS.',
                ]);
            },
        );
    }

    private function fileDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Una ruta del disco. Las relativas se resuelven desde base_path().
            $result = Pdf::file(
                resource_path('showcase/files/reporte.html')
            )->render();
            PHP;

        return $this->demo(
            slug: 'archivo',
            title: 'Archivo del disco',
            summary: 'Un HTML guardado en el sistema de archivos, sin tocarlo en memoria.',
            code: $code,
            variants: [
                $this->variant('reporte', 'Informe estático', 'Un HTML plano almacenado en `resources/`.'),
                $this->variant('error', 'Ruta inexistente', 'Qué pasa cuando el archivo no está: la excepción de la librería.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                if ($variant->key === 'error') {
                    try {
                        $this->pdf->file(resource_path('showcase/files/no-existe.html'))->render();
                    } catch (SourceNotFoundException $exception) {
                        return ShowcaseOutcome::unavailable($exception->getMessage());
                    }
                }

                $result = $this->pdf->file(resource_path('showcase/files/reporte.html'))->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'Las rutas relativas se resuelven desde `base_path()`.',
                ]);
            },
        );
    }

    private function urlDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Vellum descarga la página y la convierte en PDF.
            $result = Pdf::url('https://example.com')->render();

            // Configuración en config/pdf.php → sources.url:
            //   timeout, headers, allow_remote
            PHP;

        return $this->demo(
            slug: 'url',
            title: 'URL remota',
            summary: 'Vellum descarga la página con cURL y la convierte en PDF. Esta demo descarga un '
                .'archivo estático publicado por la propia aplicación, así que funciona sin salir a internet.',
            code: $code,
            variants: [
                $this->variant('remota', 'Página publicada', 'Se descarga de `/showcase/remoto.html`.'),
                $this->variant('error', 'URL que no responde', 'La librería lanza `SourceNotFoundException`.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                if ($variant->key === 'error') {
                    try {
                        $this->pdf->url((string) config('showcase.remote_error_url'))->render();
                    } catch (SourceNotFoundException $exception) {
                        return ShowcaseOutcome::unavailable($exception->getMessage());
                    }
                }

                $url = (string) (config('showcase.remote_url') ?: url('/showcase/remoto.html'));

                try {
                    $result = $this->pdf->url($url)->render();
                } catch (SourceNotFoundException $exception) {
                    return ShowcaseOutcome::unavailable($exception->getMessage());
                }

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'La URL descargada: '.$url,
                    'Se siguen hasta 5 redirecciones y el timeout sale de `config/pdf.php`.',
                ]);
            },
        );
    }

    private function twigDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Las plantillas Twig se buscan en sources.twig.paths.
            $result = Pdf::twig('showcase/templates/factura', [
                'factura' => $factura,
            ])->render();
            PHP;

        return $this->demo(
            slug: 'twig',
            title: 'Plantilla Twig',
            summary: 'Para los proyectos que ya usan Twig: la plantilla se resuelve en '
                .'`config/pdf.php → sources.twig.paths` y admite `nombre.twig` o `nombre`.',
            code: $code,
            variants: [
                $this->variant('factura', 'Factura', 'La misma factura, escrita en Twig.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                try {
                    $result = $this->pdf->twig('showcase/templates/factura', SampleData::invoice())->render();
                } catch (SourceNotFoundException $exception) {
                    return ShowcaseOutcome::unavailable($exception->getMessage(), [
                        ['label' => 'Instalación', 'value' => 'composer require twig/twig'],
                    ]);
                }

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'La plantilla está en `resources/views/showcase/templates/factura.twig`.',
                ]);
            },
        );
    }

    private function customDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use App\Showcase\Sources\PlantillaSource;
            use Vellum\Contracts\Source;
            use Vellum\Facades\Pdf;

            // Una fuente propia implementa el contrato Source:
            //   type(): string
            //   describe(): string
            //   load(ResolvedConfig $config): string

            $result = Pdf::from(
                new PlantillaSource('documents.invoice', ['factura' => $factura])
            )->render();
            PHP;

        return $this->demo(
            slug: 'personalizada',
            title: 'Fuente propia',
            summary: 'Implementando `Vellum\\Contracts\\Source` se puede conectar cualquier origen: '
                .'una API, una base de datos, un CMS, un archivo en la nube…',
            code: $code,
            variants: [
                $this->variant('blade', 'Resuelve una vista Blade', 'La fuente propia que usa esta demo.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $source = new PlantillaSource('documents.invoice', [
                    'factura' => SampleData::invoice(),
                ]);

                $result = $this->pdf->from($source)->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'Tipo de fuente: '.$source->type(),
                    'Descripción: '.$source->describe(),
                ]);
            },
        );
    }
}
