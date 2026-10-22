<?php

declare(strict_types=1);

namespace App\Showcase\Categories;

use App\Showcase\ShowcaseCategory;
use App\Showcase\ShowcaseCategoryBuilder;
use App\Showcase\ShowcaseDemo;
use App\Showcase\ShowcaseOutcome;
use App\Showcase\ShowcaseVariant;
use App\Showcase\Support\SampleData;
use Illuminate\Support\Facades\Route;
use Vellum\PdfManager;
use Vellum\Support\PdfResult;

/**
 * Las distintas formas de entregar el PDF al cliente.
 */
final class OutputCategory extends ShowcaseCategoryBuilder
{
    public function __construct(private readonly PdfManager $pdf) {}

    public function build(): ShowcaseCategory
    {
        return new ShowcaseCategory(
            slug: 'salida',
            title: 'Entrega del documento',
            summary: 'Un mismo PDF se puede servir en línea, descargar, guardar en disco, devolver como JSON '
                .'o servir desde un archivo temporal. Todos ellos parten del mismo `PdfResult`.',
            demos: [
                $this->inlineDemo(),
                $this->downloadDemo(),
                $this->saveDemo(),
                $this->jsonDemo(),
                $this->temporaryDemo(),
            ],
        );
    }

    private function inlineDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Se abre en el navegador (Content-Disposition: inline).
            return Pdf::view('documents.invoice', $data)
                ->filename('factura.pdf')
                ->stream();
            PHP;

        return $this->demo(
            slug: 'en-linea',
            title: 'Servido en línea',
            summary: '`->stream()` devuelve una respuesta que el navegador muestra en su visor de PDF.',
            code: $code,
            variants: [
                $this->variant('factura', 'Factura', 'La respuesta en línea de la demo.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $builder = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->filename('factura-f-2026-0042.pdf');

                $result = $builder->render();

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'Content-Type', 'value' => $result->contentType()],
                    ['label' => 'Content-Disposition', 'value' => 'inline; filename="factura-f-2026-0042.pdf"'],
                ]), [
                    'El botón «Ver en línea» de esta página hace exactamente esta llamada.',
                ]);
            },
        );
    }

    private function downloadDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Se descarga como adjunto (Content-Disposition: attachment).
            return Pdf::view('documents.invoice', $data)
                ->download('factura.pdf');

            // El argumento del método tiene prioridad sobre ->filename().
            PHP;

        return $this->demo(
            slug: 'descarga',
            title: 'Descarga',
            summary: '`->download()` fuerza la descarga y acepta el nombre del archivo como argumento.',
            code: $code,
            variants: [
                $this->variant('factura', 'Factura', 'La descarga de la demo.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'Content-Disposition', 'value' => 'attachment; filename="factura-f-2026-0042.pdf"'],
                ]), [
                    'El botón «Descargar» de esta página hace exactamente esta llamada.',
                ]);
            },
        );
    }

    private function saveDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Guarda el PDF en disco y devuelve la ruta.
            $path = Pdf::view('documents.invoice', $data)
                ->save(storage_path('app/facturas/factura.pdf'));

            // Equivalente, a partir del resultado:
            // Pdf::view(...)->render()->save($path);
            PHP;

        return $this->demo(
            slug: 'disco',
            title: 'Guardado en disco',
            summary: '`->save()` escribe el archivo y devuelve la ruta. El directorio se crea si no existe.',
            code: $code,
            variants: [
                $this->variant('factura', 'Factura', 'El archivo guardado por la demo.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $path = storage_path('app/pdf-showcase/factura-guardada.pdf');

                $ruta = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->save($path);

                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'Ruta', 'value' => $ruta],
                    ['label' => 'En disco', 'value' => is_file($ruta) ? $this->kilobytes((int) filesize($ruta)) : 'no'],
                ]), [
                    'Guardar y servir son dos cosas distintas: el archivo queda en el servidor.',
                ]);
            },
        );
    }

    private function jsonDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            $result = Pdf::view('documents.invoice', $data)->render();

            return response()->json([
                'file' => $result->toBase64(),
                'size' => $result->size(),
                'sha256' => $result->sha256(),
            ]);
            PHP;

        return $this->demo(
            slug: 'json',
            title: 'JSON con el PDF en base64',
            summary: 'Para aplicaciones que no devuelven archivos: el PDF viaja dentro de un JSON.',
            code: $code,
            variants: [
                $this->variant('factura', 'Factura', 'La respuesta JSON de la demo.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->render();

                $json = json_encode([
                    'file' => $result->toBase64(),
                    'size' => $result->size(),
                    'sha256' => $result->sha256(),
                ], JSON_THROW_ON_ERROR);

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'JSON', 'value' => $this->kilobytes(strlen($json))],
                    ['label' => 'Base64', 'value' => $this->kilobytes(strlen($result->toBase64()))],
                    ['label' => 'MD5', 'value' => $result->md5()],
                ]), [
                    'El PDF completo está en la propiedad «file», en base64.',
                ], [
                    [
                        'label' => 'Ver el JSON',
                        'url' => Route::has('pdf.showcase.json') ? route('pdf.showcase.json', [
                            'category' => 'salida',
                            'demo' => 'json',
                            'variant' => 'factura',
                        ]) : '#',
                        'kind' => 'json',
                    ],
                ]);
            },
        );
    }

    private function temporaryDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // El archivo se sirve desde una ruta temporal: ideal para
            // descargas grandes, porque no se carga todo en memoria.
            $result = Pdf::view('documents.invoice', $data)->render();

            return $result->downloadFromTemporaryFile('factura.pdf');
            PHP;

        return $this->demo(
            slug: 'temporal',
            title: 'Archivo temporal',
            summary: '`downloadFromTemporaryFile()` escribe el PDF en un archivo temporal y lo sirve desde '
                .'ahí, sin cargarlo entero en memoria.',
            code: $code,
            variants: [
                $this->variant('factura', 'Factura', 'La descarga desde archivo temporal.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'Tipo de respuesta', 'value' => 'BinaryFileResponse'],
                    ['label' => 'Archivo', 'value' => 'temporal, se borra al terminar la respuesta'],
                ]), [
                    'Solo está disponible en `PdfResult`: el constructor no la expone.',
                ]);
            },
        );
    }

    /**
     * Resultado de la demo, reutilizado por el endpoint JSON.
     */
    public function resultado(ShowcaseVariant $variant): PdfResult
    {
        return $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->render();
    }
}
