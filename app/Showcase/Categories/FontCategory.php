<?php

declare(strict_types=1);

namespace App\Showcase\Categories;

use App\Showcase\Fonts\FontCatalog;
use App\Showcase\Fonts\FontFamily;
use App\Showcase\ShowcaseCategory;
use App\Showcase\ShowcaseCategoryBuilder;
use App\Showcase\ShowcaseDemo;
use App\Showcase\ShowcaseOutcome;
use App\Showcase\ShowcaseVariant;
use Vellum\PdfManager;
use Vellum\Support\PdfResult;

/**
 * Las fuentes tipográficas: qué puede usar cada motor, cómo se ve el documento
 * con cada familia y cuánto pesa el PDF según cómo se incruste.
 */
final class FontCategory extends ShowcaseCategoryBuilder
{
    public function __construct(
        private readonly PdfManager $pdf,
        private readonly FontCatalog $fonts,
    ) {}

    public function build(): ShowcaseCategory
    {
        return new ShowcaseCategory(
            slug: 'tipografias',
            title: 'Tipografías',
            summary: 'Cada motor ve un catálogo distinto de fuentes. Aquí están todas las familias que esta '
                .'instalación puede usar, el mismo documento con cada una y lo que cambia en el PDF al incrustarlas.',
            demos: [
                $this->familiesDemo(),
                $this->scriptsDemo(),
                $this->driversDemo(),
                $this->weightDemo(),
                $this->embedDemo(),
            ],
            aside: [
                'view' => 'showcase.partials.font-catalog',
                'data' => [
                    'fonts' => $this->fonts->all(),
                    'totals' => $this->fonts->totals(),
                    'bundled' => $this->fonts->bundled(),
                ],
            ],
        );
    }

    /**
     * Una variante por familia con cobertura latina: el mismo texto, solo
     * cambia la tipografía.
     */
    private function familiesDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Cada motor tiene su propio catálogo:
            //   · Chromium ve las fuentes instaladas en el sistema.
            //   · dompdf y mPDF, solo las de su carpeta de fuentes.
            $result = Pdf::view('documents.tipografia', $data)
                ->driver('browsershot')
                ->font('Nimbus Sans')
                ->render();
            PHP;

        $variants = [];

        foreach ($this->fonts->latin() as $font) {
            $variants[] = $this->variant(
                $font->key,
                $font->family,
                $font->kindLabel().' · '.implode(', ', $font->driverLabels()),
            );
        }

        return $this->demo(
            slug: 'familias',
            title: 'Familias latinas',
            summary: 'Una variante por familia con cobertura latina. El texto y la maquetación son idénticos: solo cambia la fuente.',
            code: $code,
            variants: $variants,
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $font = $this->fonts->find($variant->key);

                if ($font === null) {
                    return ShowcaseOutcome::unavailable(
                        'La familia «'.$variant->key.'» ya no está instalada en esta máquina.'
                    );
                }

                $driver = $font->preferredDriver();

                if ($driver === null) {
                    return ShowcaseOutcome::unavailable('Ningún motor instalado puede usar esta familia.');
                }

                $result = $this->pdf
                    ->view('documents.tipografia', $this->specimen($font, $driver))
                    ->driver($driver)
                    ->font($font->family)
                    ->render();

                return ShowcaseOutcome::ok(
                    $result->bytes,
                    $this->fontMetrics($result, $font),
                    [
                        $font->note,
                        'Se renderiza con «'.$driver.'», el motor que puede ver esta familia.',
                        'El peso depende de los glifos que usa el documento: Chromium incrusta un subconjunto y dompdf, la fuente completa.',
                    ],
                );
            },
        );
    }

    /**
     * Las familias que cubren otros alfabetos, con un espécimen en su script.
     */
    private function scriptsDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // mPDF aplica el shaping (OTL) de árabe, hebreo, tailandés o birmano:
            // la familia tiene que estar en su catálogo de fuentes.
            $result = Pdf::view('documents.alfabeto', $data)
                ->driver('mpdf')
                ->font('XB Riyaz')
                ->render();
            PHP;

        $scripts = [
            'cjk' => [
                'font' => 'noto-sans-cjk-sc',
                'title' => 'Chino, japonés y coreano',
                'coverage' => 'CJK',
                'language' => 'zh',
                'texts' => [
                    ['label' => 'Chino simplificado', 'text' => '你好，世界。这是一段用于检查中文排版的示例文字。'],
                    ['label' => 'Japonés', 'text' => 'こんにちは世界。これは日本語の組版を確認するための文章です。'],
                    ['label' => 'Coreano', 'text' => '안녕하세요 세계. 이것은 한국어 조판을 확인하기 위한 문장입니다.'],
                ],
            ],
            'arabe' => [
                'font' => 'xb-riyaz',
                'title' => 'Árabe',
                'coverage' => 'Árabe',
                'language' => 'ar',
                'texts' => [
                    ['label' => 'Árabe', 'text' => 'مرحبًا بالعالم، هذا نصٌّ تجريبيٌّ لفحص الطباعة العربية. الأرقام: ١٢٣٤٥٦٧٨٩٠', 'rtl' => true],
                ],
            ],
            'hebreo' => [
                'font' => 'taamey-david-clm',
                'title' => 'Hebreo',
                'coverage' => 'Hebreo',
                'language' => 'he',
                'texts' => [
                    ['label' => 'Hebreo', 'text' => 'שלום עולם, זהו טקסט לבדיקה של עימוד עברי.', 'rtl' => true],
                ],
            ],
            'tailandes' => [
                'font' => 'garuda',
                'title' => 'Tailandés',
                'coverage' => 'Tailandés',
                'language' => 'th',
                'texts' => [
                    ['label' => 'Tailandés', 'text' => 'สวัสดีชาวโลก นี่คือข้อความสำหรับทดสอบการจัดพิมพ์ภาษาไทย'],
                ],
            ],
            'birmano' => [
                'font' => 'padauk-book',
                'title' => 'Birmano',
                'coverage' => 'Birmano',
                'language' => 'my',
                'texts' => [
                    ['label' => 'Birmano', 'text' => 'မင်္ဂလာပါ ကမ္ဘာလောက · မြန်မာစာ'],
                ],
            ],
        ];

        $variants = [
            $this->variant('cjk', 'CJK · chino, japonés y coreano', 'Noto Sans CJK SC en Chromium.'),
            $this->variant('arabe', 'Árabe · XB Riyaz', 'mPDF con shaping OTL y kashida.'),
            $this->variant('hebreo', 'Hebreo · Taamey David CLM', 'mPDF con niqud y cantilación.'),
            $this->variant('tailandes', 'Tailandés · Garuda', 'mPDF con shaping OTL.'),
            $this->variant('birmano', 'Birmano · Padauk Book', 'mPDF con shaping OTL.'),
        ];

        return $this->demo(
            slug: 'alfabetos',
            title: 'Otros alfabetos',
            summary: 'Las familias que mPDF y fontconfig aportan para árabe, hebreo, CJK, tailandés y birmano. El espécimen usa texto real de cada script.',
            code: $code,
            variants: $variants,
            handler: function (ShowcaseVariant $variant) use ($scripts): ShowcaseOutcome {
                $script = $scripts[$variant->key] ?? null;

                if ($script === null) {
                    return ShowcaseOutcome::unavailable('Este alfabeto no está configurado.');
                }

                $font = $this->fonts->find($script['font']);

                if ($font === null) {
                    return ShowcaseOutcome::unavailable(
                        'La familia «'.$script['font'].'» no está disponible en esta instalación.'
                    );
                }

                $driver = $font->preferredDriver();

                if ($driver === null) {
                    return ShowcaseOutcome::unavailable('Ningún motor instalado puede usar «'.$font->family.'».');
                }

                $result = $this->pdf
                    ->view('documents.alfabeto', [
                        'familia' => $font->family,
                        'origen' => $font->originLabel().' · '.$driver,
                        'cobertura' => $script['coverage'],
                        'titulo' => $script['title'],
                        'textos' => $script['texts'],
                        'idioma' => $script['language'],
                    ])
                    ->driver($driver)
                    ->font($font->family)
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, $this->fontMetrics($result, $font), [
                    'El mismo HTML y el mismo espécimen: solo cambia la familia y el alfabeto del texto.',
                    'mPDF aplica el shaping OpenType (OTL) de árabe, hebreo, tailandés y birmano; Chromium lo hace con HarfBuzz.',
                    'Si la familia no cubre el latino, el motor cae en su fuente por defecto para esa línea.',
                ]);
            },
        );
    }

    /**
     * La misma familia en los tres motores.
     */
    private function driversDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // DejaVu Sans está en el catálogo de los tres motores.
            foreach (['dompdf', 'mpdf', 'browsershot'] as $driver) {
                $result = Pdf::view('documents.tipografia', $data)
                    ->driver($driver)
                    ->font('DejaVu Sans')
                    ->render();
            }
            PHP;

        return $this->demo(
            slug: 'motores',
            title: 'La misma fuente en cada motor',
            summary: 'DejaVu Sans renderizada con dompdf, mPDF y Chromium. Mismo HTML, distinto motor y distinto peso.',
            code: $code,
            variants: [
                $this->variant('dompdf', 'dompdf', 'Incrusta su copia de DejaVu Sans.'),
                $this->variant('mpdf', 'mPDF', 'Resuelve la familia desde su carpeta ttfonts.'),
                $this->variant('browsershot', 'Chromium', 'Usa la fuente instalada en el sistema.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $result = $this->pdf
                    ->view('documents.tipografia', [
                        'familia' => 'DejaVu Sans',
                        'origen' => 'Catálogo de '.$variant->key,
                        'archivo' => 'DejaVuSans.ttf',
                    ])
                    ->driver($variant->key)
                    ->font('DejaVu Sans')
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'El HTML es el mismo en las tres variantes: solo cambia el motor.',
                    'dompdf incrusta la fuente completa; Chromium, un subconjunto de los glifos usados; mPDF escribe su propia copia.',
                    'Chromium tarda más porque arranca un navegador real para cada render.',
                ]);
            },
        );
    }

    /**
     * Cuánto pesa la tipografía según cómo se declare.
     */
    private function weightDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // 1) Fuente del núcleo PDF: no se incrusta.
            Pdf::view('documents.tipografia', $data)
                ->font('Helvetica')
                ->render();

            // 2) Familia del catálogo del motor: se incrusta.
            Pdf::view('documents.tipografia', $data)
                ->font('DejaVu Sans')
                ->render();

            // 3) Archivo TTF propio: se incrusta desde el disco.
            Pdf::view('documents.tipografia', $data)
                ->font('Vellum Sans', base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'))
                ->render();
            PHP;

        return $this->demo(
            slug: 'peso',
            title: 'Cuánto pesa la tipografía',
            summary: 'El mismo documento con una fuente del núcleo, con una del catálogo y con un TTF propio: la diferencia de tamaño está en si la fuente se incrusta.',
            code: $code,
            variants: [
                $this->variant('core', 'Sin incrustar · Helvetica', 'Fuente del núcleo PDF: el lector pone la suya.'),
                $this->variant('catalogo', 'Catálogo · DejaVu Sans', 'El motor incrusta su copia de la fuente.'),
                $this->variant('archivo', 'TTF propio · Vellum Sans', 'La fuente se incrusta desde un archivo del proyecto.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $builder = $this->pdf->view('documents.tipografia', [
                    'familia' => match ($variant->key) {
                        'core' => 'Helvetica',
                        'catalogo' => 'DejaVu Sans',
                        default => 'Vellum Sans',
                    },
                    'origen' => 'dompdf',
                    'archivo' => $variant->key === 'archivo' ? basename($this->fontFile()) : null,
                ]);

                $builder = match ($variant->key) {
                    'core' => $builder->font('Helvetica'),
                    'catalogo' => $builder->font('DejaVu Sans'),
                    default => $builder->font('Vellum Sans', $this->fontFile()),
                };

                $result = $builder->driver('dompdf')->render();

                return ShowcaseOutcome::ok($result->bytes, [
                    ['label' => 'Incrustada', 'value' => match ($variant->key) {
                        'core' => 'No · núcleo PDF',
                        'catalogo' => 'Sí · catálogo del motor',
                        default => 'Sí · archivo propio',
                    }],
                    ...$this->metrics($result),
                ], [
                    'Helvetica, Times y Courier son fuentes del núcleo PDF: no se incrustan, por eso el archivo pesa apenas unos kilobytes. A cambio, el lector decide cómo se ven y el juego de glifos es limitado.',
                    'DejaVu Sans se incrusta entera: el documento se ve igual en cualquier lector, pero el archivo crece.',
                    'Al incrustar un TTF con `->font(\'Alias\', $archivo)` solo se registra esa cara; las negritas caen en la fuente por defecto del motor salvo que registres también sus variantes.',
                ]);
            },
        );
    }

    /**
     * Dónde funciona un TTF propio y dónde no.
     */
    private function embedDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // dompdf incrusta el archivo con @font-face y lo usa como familia.
            $result = Pdf::view('documents.tipografia', $data)
                ->driver('dompdf')
                ->font('Vellum Sans', base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'))
                ->render();
            PHP;

        return $this->demo(
            slug: 'incrustar',
            title: 'Incrustar un TTF propio',
            summary: '`font()` acepta un segundo argumento con el archivo de la fuente. No todos los motores lo aprovechan igual.',
            code: $code,
            variants: [
                $this->variant('dompdf', 'dompdf · funciona', 'Inyecta el @font-face y renderiza con el TTF.'),
                $this->variant('mpdf', 'mPDF · no lee el archivo', 'mPDF resuelve las familias desde su propio catálogo.'),
                $this->variant('browsershot', 'Chromium · bloquea file://', 'Browsershot rechaza el HTML con rutas file://.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                if ($variant->key === 'mpdf') {
                    return ShowcaseOutcome::unavailable(
                        'mPDF no usa el archivo de `fontFile`: busca las familias en su carpeta `ttfonts` y en su '
                        .'configuración de fuentes. Para una tipografía propia hay que registrarla en mPDF y '
                        .'referenciarla por su nombre.'
                    );
                }

                if ($variant->key === 'browsershot') {
                    return ShowcaseOutcome::unavailable(
                        'Browsershot rechaza cualquier HTML que contenga `file://`, así que el `@font-face` con el '
                        .'archivo local no llega a Chromium. Usa una fuente instalada en el sistema o un `@font-face` '
                        .'con una URL HTTP servida por la aplicación.'
                    );
                }

                $result = $this->pdf
                    ->view('documents.tipografia', [
                        'familia' => 'Vellum Sans',
                        'origen' => 'dompdf · @font-face',
                        'archivo' => basename($this->fontFile()),
                    ])
                    ->driver('dompdf')
                    ->font('Vellum Sans', $this->fontFile())
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'Vellum inyecta `@font-face{...}` en el HTML y pasa el TTF a dompdf, que lo incrusta en el PDF.',
                    'La negrita de este documento cae en la fuente por defecto de dompdf: el archivo solo registra una cara.',
                    'Si la fuente es de marca, registra también sus variantes o usa Chromium, que aprovecha las que trae el sistema.',
                ]);
            },
        );
    }

    /**
     * Datos que recibe la plantilla del espécimen.
     *
     * @return array{familia: string, origen: string, archivo: string|null}
     */
    private function specimen(FontFamily $font, string $driver): array
    {
        return [
            'familia' => $font->family,
            'origen' => $font->originLabel().' · '.$driver,
            'archivo' => $font->fileName(),
        ];
    }

    /**
     * Métricas del render con los datos de la familia delante.
     *
     * @return list<array{label: string, value: string}>
     */
    private function fontMetrics(PdfResult $result, FontFamily $font): array
    {
        return [
            ['label' => 'Familia', 'value' => $font->family],
            ['label' => 'Tipo', 'value' => $font->kindLabel()],
            ['label' => 'Cobertura', 'value' => $font->script],
            ['label' => 'Origen', 'value' => $font->originLabel()],
            ...$this->metrics($result),
        ];
    }

    /**
     * TTF de ejemplo que viene con dompdf.
     */
    private function fontFile(): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
    }
}
