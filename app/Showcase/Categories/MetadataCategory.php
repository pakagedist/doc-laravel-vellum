<?php

declare(strict_types=1);

namespace App\Showcase\Categories;

use App\Showcase\ShowcaseCategory;
use App\Showcase\ShowcaseCategoryBuilder;
use App\Showcase\ShowcaseDemo;
use App\Showcase\ShowcaseOutcome;
use App\Showcase\ShowcaseVariant;
use App\Showcase\Support\SampleData;
use Vellum\PdfManager;

/**
 * Metadatos del documento, XMP, PDF/A y Factur-X.
 */
final class MetadataCategory extends ShowcaseCategoryBuilder
{
    public function __construct(private readonly PdfManager $pdf) {}

    public function build(): ShowcaseCategory
    {
        return new ShowcaseCategory(
            slug: 'metadatos',
            title: 'Metadatos y conformidad',
            summary: 'El PDF lleva información propia: el diccionario `/Info`, el paquete XMP, la marca PDF/A '
                .'y, en el caso de las facturas, los datos de Factur-X/ZUGFeRD para que sea legible por una máquina.',
            demos: [
                $this->metadataDemo(),
                $this->xmpDemo(),
                $this->pdfaDemo(),
                $this->zugferdDemo(),
            ],
        );
    }

    private function metadataDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            $result = Pdf::view('documents.invoice', $data)
                ->metadata([
                    'title' => 'Factura F-2026-0042',
                    'author' => 'Vellum Fábrica de PDF, S.L.',
                    'subject' => 'Factura mensual',
                    'keywords' => 'factura, 2026, vellum',
                    'creator' => 'Aplicación ATD',
                    'language' => 'es',
                    // Cualquier clave extra se escribe como entrada personalizada del /Info:
                    'VellumReference' => 'REF-0042',
                ])
                ->render();

            // También con el objeto tipado:
            // ->metadata(DocumentMetadata::fromArray([...]));
            PHP;

        return $this->demo(
            slug: 'metadatos',
            title: 'Diccionario /Info',
            summary: 'Título, autor, asunto, palabras clave, productor, idioma y entradas personalizadas. '
                .'Es lo que ven los exploradores y los buscadores al abrir el archivo.',
            code: $code,
            variants: [
                $this->variant('basico', 'Metadatos básicos', 'Título, autor y asunto.'),
                $this->variant('completos', 'Metadatos completos', 'Con fechas, idioma y claves personalizadas.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $metadata = $variant->key === 'completos'
                    ? [
                        'title' => 'Factura F-2026-0042',
                        'author' => 'Vellum Fábrica de PDF, S.L.',
                        'subject' => 'Factura mensual de servicios',
                        'keywords' => 'factura, 2026, vellum, atd',
                        'creator' => 'Aplicación ATD',
                        'producer' => 'laravel-vellum',
                        'language' => 'es',
                        'creation_date' => 'D:20261022120000+00\'00\'',
                        'modification_date' => 'D:20261022120000+00\'00\'',
                        'VellumReference' => 'REF-0042',
                        'VellumStatus' => 'emitida',
                    ]
                    : [
                        'title' => 'Factura F-2026-0042',
                        'author' => 'Vellum Fábrica de PDF, S.L.',
                        'subject' => 'Factura mensual de servicios',
                    ];

                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->metadata($metadata)
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'Las claves que no son estándar se guardan tal cual en el /Info.',
                    'Se pueden leer con `DocumentMetadata::fromArray()` o con cualquier visor de PDF.',
                ]);
            },
        );
    }

    private function xmpDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            $result = Pdf::view('documents.invoice', $data)
                ->xmp([
                    'dc:title' => 'Factura F-2026-0042',
                    'dc:creator' => 'Vellum Fábrica de PDF, S.L.',
                ])
                // Un namespace propio, si hace falta:
                ->xmpNamespace('vellum', 'https://laravel-vellum.test/ns/1.0')
                ->render();
            PHP;

        return $this->demo(
            slug: 'xmp',
            title: 'Paquete XMP',
            summary: 'XMP es el estándar de metadatos para archivos为先. Vellum escribe un paquete RDF '
                .'completo y lo enlaza desde el catálogo del PDF.',
            code: $code,
            variants: [
                $this->variant('basico', 'XMP estándar', 'Propiedades Dublin Core.'),
                $this->variant('namespace', 'Con namespace propio', 'Propiedades de la aplicación.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $builder = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->xmp([
                        'dc:title' => 'Factura F-2026-0042',
                        'dc:creator' => 'Vellum Fábrica de PDF, S.L.',
                        'dc:description' => 'Factura emitida por la aplicación de demostración.',
                    ]);

                if ($variant->key === 'namespace') {
                    $builder = $builder->xmpNamespace('vellum', 'https://laravel-vellum.test/ns/1.0');
                }

                $result = $builder->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'El paquete XMP viaja como un stream `/Metadata` enlazado en el catálogo.',
                ]);
            },
        );
    }

    private function pdfaDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Marca el documento como PDF/A-3b con el perfil ICC incluido.
            $result = Pdf::view('documents.invoice', $data)
                ->pdfa(resource_path('pdf/srgb.icc'))
                ->render();

            // Sin perfil, el documento queda marcado pero sin /OutputIntent:
            // ->pdfa();
            PHP;

        return $this->demo(
            slug: 'pdfa',
            title: 'PDF/A-3b',
            summary: 'PDF/A es el formato de archivo PDF para la conservación a largo plazo. La demo marca '
                .'el documento con `pdfaid:part=3`, `pdfaid:conformance=B` y un `/OutputIntent` con el '
                .'perfil ICC sRGB que viaja en `resources/pdf/srgb.icc`.',
            code: $code,
            variants: [
                $this->variant('pdfa3b', 'PDF/A-3b', 'Marcado de conformidad y perfil de color.'),
                $this->variant('sinpdfa', 'Sin marcar', 'El mismo documento, para comparar.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $builder = $this->pdf->view('documents.invoice', ['factura' => SampleData::invoice()]);

                if ($variant->key === 'pdfa3b') {
                    $builder = $builder->pdfa(resource_path('pdf/srgb.icc'));
                }

                $result = $builder->render();

                $marcado = str_contains($result->bytes, 'pdfaid:part') ? 'sí' : 'no';

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'Marcado PDF/A', 'value' => $marcado],
                ]), [
                    'El perfil ICC de `resources/pdf/srgb.icc` se escribe como `/OutputIntent` (`GTS_PDFA1`).',
                ]);
            },
        );
    }

    private function zugferdDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Factur-X / ZUGFeRD: XML incrustado + PDF/A-3b + relación "Alternative".
            $result = Pdf::view('documents.invoice', $data)
                ->zugferd(base_path('resources/showcase/factur-x.xml'), [
                    'document_type' => 'INVOICE',
                    'conformance_level' => 'EN 16931',
                    'version' => '1.0',
                ])
                ->render();
            PHP;

        return $this->demo(
            slug: 'zugferd',
            title: 'Factur-X / ZUGFeRD',
            summary: 'Factur-X (la versión europea de ZUGFeRD) incrusta un XML de factura en el PDF. '
                .'Es lo que permite a un programa de facturación leer la factura sin OCR.',
            code: $code,
            variants: [
                $this->variant('facturx', 'Factur-X EN 16931', 'El XML va incrustado como archivo alterno.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $xml = base_path('resources/showcase/factur-x.xml');

                if (! is_file($xml)) {
                    return ShowcaseOutcome::unavailable('No se encuentra el XML de ejemplo.', [
                        ['label' => 'Ruta esperada', 'value' => $xml],
                    ]);
                }

                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->zugferd($xml, [
                        'document_type' => 'INVOICE',
                        'conformance_level' => 'EN 16931',
                        'version' => '1.0',
                    ])
                    ->render();

                $incrustado = str_contains($result->bytes, 'factur-x.xml') ? 'sí' : 'no';

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'XML incrustado', 'value' => $incrustado],
                    ['label' => 'Relación', 'value' => 'Alternative (machine readable)'],
                    ['label' => 'Conformidad', 'value' => 'PDF/A-3b'],
                ]), [
                    'El XML lo genera la aplicación: Vellum solo lo incrusta y marca el PDF.',
                ]);
            },
        );
    }
}
