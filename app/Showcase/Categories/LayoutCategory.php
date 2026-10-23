<?php

declare(strict_types=1);

namespace App\Showcase\Categories;

use App\Showcase\ShowcaseCategory;
use App\Showcase\ShowcaseCategoryBuilder;
use App\Showcase\ShowcaseDemo;
use App\Showcase\ShowcaseOutcome;
use App\Showcase\ShowcaseVariant;
use App\Showcase\Support\SampleData;
use Vellum\Config\PaperSize;
use Vellum\PdfManager;

/**
 * Cómo se maqueta el documento: papel, orientación, márgenes, resolución y tipografías.
 */
final class LayoutCategory extends ShowcaseCategoryBuilder
{
    public function __construct(private readonly PdfManager $pdf) {}

    public function build(): ShowcaseCategory
    {
        return new ShowcaseCategory(
            slug: 'maquetacion',
            title: 'Papel, márgenes y tipografía',
            summary: 'El documento decide su maquetación. Las reglas `@page { size: … }` de la plantilla se '
                .'descartan, porque cada motor las interpreta de forma distinta: el tamaño sale de la API.',
            demos: [
                $this->paperDemo(),
                $this->orientationDemo(),
                $this->marginsDemo(),
                $this->dpiDemo(),
                $this->fontDemo(),
            ],
        );
    }

    private function paperDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Ocho tamaños disponibles: a3, a4, a5, b5, letter, legal, tabloid y executive.
            $result = Pdf::view('documents.invoice', $data)
                ->paper('a5')
                ->render();

            // Tamaño y orientación a la vez:
            // ->paper('a4', 'landscape');
            PHP;

        $variants = [];

        foreach (PaperSize::cases() as $case) {
            $variants[] = $this->variant(
                $case->value,
                $case->label(),
                sprintf('%d × %d mm', (int) $case->widthInMillimetres(), (int) $case->heightInMillimetres()),
            );
        }

        return $this->demo(
            slug: 'papel',
            title: 'Tamaño de papel',
            summary: 'Los ocho tamaños que admite `->paper()`, con sus medidas reales en milímetros.',
            code: $code,
            variants: $variants,
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->paper($variant->key)
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'El MediaBox del PDF refleja el tamaño pedido.',
                    'Las medidas exactas están en `Vellum\Config\PaperSize`.',
                ]);
            },
        );
    }

    private function orientationDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            $result = Pdf::view('documents.users', $data)
                ->orientation('landscape')
                ->render();

            // También se puede pedir al mismo tiempo:
            // ->paper('a4', 'landscape');
            PHP;

        return $this->demo(
            slug: 'orientacion',
            title: 'Orientación',
            summary: 'Vertical (portrait) y apaisado (landscape) sin tocar la plantilla.',
            code: $code,
            variants: [
                $this->variant('portrait', 'Vertical', 'La orientación por defecto.'),
                $this->variant('landscape', 'Apaisado', 'El mismo documento, girado.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $result = $this->pdf
                    ->view('documents.informe', ['users' => SampleData::users(), 'generatedAt' => now()])
                    ->orientation($variant->key)
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'El MediaBox cambia de 595×842 a 842×595 pt.',
                ]);
            },
        );
    }

    private function marginsDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Los márgenes van en milímetros, en este orden:
            // superior, derecho, inferior, izquierdo.
            $result = Pdf::view('documents.invoice', $data)
                ->margins(10, 10, 10, 10)
                ->render();

            // En DOMPDF se inyectan como @page { margin: … }.
            // En mPDF, con SetMargins() y SetAutoPageBreak().
            // En Chromium, en pulgadas.
            PHP;

        return $this->demo(
            slug: 'margenes',
            title: 'Márgenes',
            summary: 'Cada motor los aplica a su manera, pero el resultado es el mismo.',
            code: $code,
            variants: [
                $this->variant('normal', '10 mm por lado', 'El valor de `config/pdf.php`.'),
                $this->variant('estrecho', '5 mm por lado', 'Paraolistados densos.'),
                $this->variant('ancho', '25 mm por lado', 'Para encuadernar.'),
                $this->variant('cabecera', '10 / 10 / 40 / 10', 'Pie grande para la firma.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $margenes = match ($variant->key) {
                    'estrecho' => [5.0, 5.0, 5.0, 5.0],
                    'ancho' => [25.0, 25.0, 25.0, 25.0],
                    'cabecera' => [10.0, 10.0, 40.0, 10.0],
                    default => [10.0, 10.0, 10.0, 10.0],
                };

                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->margins(...$margenes)
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'Márgenes aplicados: '.implode(' / ', $margenes).' mm.',
                ]);
            },
        );
    }

    private function dpiDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // La resolución con la que se dibuja el contenido.
            $result = Pdf::view('documents.invoice', $data)
                ->dpi(300)
                ->render();
            PHP;

        return $this->demo(
            slug: 'resolucion',
            title: 'Resolución (DPI)',
            summary: 'Afecta sobre todo a las imágenes y a los trazos: más DPI, más peso y más nitidez.',
            code: $code,
            variants: [
                $this->variant('72', '72 DPI', 'Ligero, pensado para pantalla.'),
                $this->variant('96', '96 DPI', 'El valor por defecto.'),
                $this->variant('150', '150 DPI', 'Buen equilibrio para imprimir.'),
                $this->variant('300', '300 DPI', 'Calidad de imprenta.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->dpi((int) $variant->key)
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'DPI aplicado: '.$variant->key.'.',
                ]);
            },
        );
    }

    private function fontDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Solo el nombre de la familia…
            $result = Pdf::view('documents.invoice', $data)
                ->font('DejaVu Sans')
                ->render();

            // …o un archivo TTF propio, que se incrusta con @font-face.
            $result = Pdf::view('documents.invoice', $data)
                ->font('Vellum Sans', base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'))
                ->render();
            PHP;

        return $this->demo(
            slug: 'tipografia',
            title: 'Tipografía en la maquetación',
            summary: 'Se puede forzar la familia tipográfica o incrustar un archivo TTF propio.',
            code: $code,
            variants: [
                $this->variant('serif', 'Serif (por defecto)', 'La tipografía base de DOMPDF.'),
                $this->variant('sans', 'Sans-serif', 'Cambia solo el CSS del documento.'),
                $this->variant('ttf', 'Archivo TTF incrustado', 'Incrusta una tipografía del propio proyecto.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $builder = $this->pdf->view('documents.invoice', ['factura' => SampleData::invoice()]);

                $builder = match ($variant->key) {
                    'sans' => $builder->font('DejaVu Sans'),
                    'ttf' => $builder->font('Vellum Sans', $this->fontFile()),
                    default => $builder->font('DejaVu Serif'),
                };

                $result = $builder->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'Vellum inyecta el `font-family` (y el `@font-face` si hay archivo) en el HTML.',
                    'El módulo de tipografías reúne todas las familias disponibles, su tamaño y el motor que puede usarlas.',
                ], [
                    ['label' => 'Abrir el módulo de tipografías', 'url' => route('pdf.category', 'tipografias'), 'kind' => 'internal'],
                ]);
            },
        );
    }

    /**
     * Archivo TTF de ejemplo que viene con DOMPDF.
     */
    private function fontFile(): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
    }
}
