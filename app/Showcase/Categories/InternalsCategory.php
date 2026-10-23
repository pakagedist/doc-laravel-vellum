<?php

declare(strict_types=1);

namespace App\Showcase\Categories;

use App\Showcase\Drivers\ShowcaseDriver;
use App\Showcase\Processors\WatermarkProcessor;
use App\Showcase\ShowcaseCategory;
use App\Showcase\ShowcaseCategoryBuilder;
use App\Showcase\ShowcaseDemo;
use App\Showcase\ShowcaseOutcome;
use App\Showcase\ShowcaseVariant;
use App\Showcase\Support\SampleData;
use Vellum\Config\ConfigResolver;
use Vellum\Drivers\DriverRegistry;
use Vellum\PdfManager;
use Vellum\Pipeline\Pipeline;
use Vellum\Support\PdfResult;

/**
 * Cómo funciona Vellum por dentro: la configuración, el registro de motores,
 * la cadena de post-procesado y las pruebas.
 */
final class InternalsCategory extends ShowcaseCategoryBuilder
{
    public function __construct(
        private readonly PdfManager $pdf,
        private readonly DriverRegistry $drivers,
        private readonly ConfigResolver $resolver,
        private readonly Pipeline $pipeline,
    ) {}

    public function build(): ShowcaseCategory
    {
        return new ShowcaseCategory(
            slug: 'internos',
            title: 'Funcionamiento interno',
            summary: 'Lo que hay por debajo de la API: cómo se resuelve la configuración, cómo se registran '
                .'los motores, en qué orden se ejecutan los post-procesadores y cómo se prueba sin renderizar.',
            demos: [
                $this->configDemo(),
                $this->registryDemo(),
                $this->pipelineDemo(),
                $this->testingDemo(),
            ],
        );
    }

    private function configDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Config\ConfigResolver;

            // La configuración se resuelve en tres capas, de menor a mayor:
            //   defaults  <  drivers.<motor>  <  overrides del documento
            $config = app(ConfigResolver::class)->resolve('mpdf');

            $config->paper;         // Vellum\Config\PaperSize
            $config->margins;       // Vellum\Config\Margins (en mm)
            $config->dpi;
            $config->timeout;
            $config->options;       // opciones del motor
            PHP;

        return $this->demo(
            slug: 'configuracion',
            title: 'Capas de configuración',
            summary: 'Lo que el motor va a hacer, después de combinar `config/pdf.php`, los valores '
                .'particulares del motor y los que se indican en el documento.',
            code: $code,
            variants: [
                $this->variant('dompdf', 'Resolución para DOMPDF', 'La configuración que se aplica al motor por defecto.'),
                $this->variant('mpdf', 'Resolución para mPDF', 'Los valores propios del motor.'),
                $this->variant('browsershot', 'Resolución para Chromium', 'Con las opciones de Puppeteer.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $config = $this->resolver->resolve($variant->key);

                $metrics = [
                    ['label' => 'Motor', 'value' => $config->driver],
                    ['label' => 'Papel', 'value' => $config->paper->value.' ('.$config->paper->label().')'],
                    ['label' => 'Orientación', 'value' => $config->orientation->value],
                    ['label' => 'DPI', 'value' => (string) $config->dpi],
                    ['label' => 'Márgenes', 'value' => $config->margins->toCss()],
                    ['label' => 'Timeout', 'value' => $config->timeout.' s'],
                    ['label' => 'Tipografía', 'value' => $config->font ?? 'la del motor'],
                    ['label' => 'Recursos remotos', 'value' => $config->allowRemoteAssets ? 'permitidos' : 'bloqueados'],
                    ['label' => 'Avisos = error', 'value' => $config->failOnWarnings ? 'sí' : 'no'],
                    ['label' => 'Opciones del motor', 'value' => $config->options === [] ? '—' : json_encode($config->options, JSON_THROW_ON_ERROR)],
                ];

                return ShowcaseOutcome::ok(null, $metrics, [
                    'Los valores salen de `config/pdf.php`, que la librería publica con `vendor:publish --tag=pdf-config`.',
                ]);
            },
        );
    }

    private function registryDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Registrar un motor propio para toda la aplicación…
            Pdf::register(new MiMotor);

            // …o añadirlo con nombre, para usarlo por documento:
            Pdf::extend('mi-motor', fn () => new MiMotor);

            Pdf::hasDriver('mi-motor');  // true
            Pdf::driver('mi-motor');     // Vellum\Contracts\Driver
            PHP;

        return $this->demo(
            slug: 'registro',
            title: 'Registro de motores',
            summary: 'El registro de motores es un singleton del contenedor. Se pueden añadir motores '
                .'propios, que es la forma de conectar un motor que no venga de serie.',
            code: $code,
            variants: [
                $this->variant('listado', 'Motores registrados', 'Los que hay ahora mismo.'),
                $this->variant('propio', 'Motor propio', 'Un motor registrado desde la aplicación.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                if ($variant->key === 'propio') {
                    $this->pdf->register(new ShowcaseDriver, 'showcase');
                }

                $metrics = [];

                foreach ($this->drivers->all() as $name => $driver) {
                    $metrics[] = [
                        'label' => $name,
                        'value' => $driver::class,
                    ];
                }

                return ShowcaseOutcome::ok(null, $metrics, [
                    'Un motor propio solo tiene que implementar `Vellum\Contracts\Driver`.',
                    'La app también puede pedir un motor que no exista: la librería lanza `DriverNotSupportedException`.',
                ]);
            },
        );
    }

    private function pipelineDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Pipeline\Pipeline;

            // Los post-procesadores globales salen de config/pdf.php → processors.
            $processors = app(Pipeline::class)->processors();

            // Y los del documento, con ->process(...), se ejecutan después.
            PHP;

        return $this->demo(
            slug: 'procesadores',
            title: 'Orden de los post-procesadores',
            summary: 'El orden es siempre el mismo: los globales de la configuración, después los del '
                .'documento. Cada uno recibe el PDF del anterior.',
            code: $code,
            variants: [
                $this->variant('globales', 'Procesadores globales', 'Los declarados en `config/pdf.php`.'),
                $this->variant('documento', 'Procesadores del documento', 'Los que se añaden con `->process()`.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $globales = array_map(
                    static fn ($processor): string => $processor->name(),
                    $this->pipeline->processors(),
                );

                $metrics = [
                    ['label' => 'Procesadores globales', 'value' => $globales === [] ? 'ninguno' : implode(' · ', $globales)],
                    ['label' => 'Procesadores del documento', 'value' => 'watermark (en esta demo)'],
                    ['label' => 'Orden', 'value' => 'globales → documento'],
                ];

                if ($variant->key === 'documento') {
                    $result = $this->pdf
                        ->view('documents.invoice', ['factura' => SampleData::invoice()])
                        ->process(new WatermarkProcessor('CONFIDENCIAL'))
                        ->render();

                    return ShowcaseOutcome::ok($result->bytes, array_merge($metrics, $this->metrics($result)), [
                        'El post-procesador del documento se ha aplicado: el PDF lleva la marca.',
                    ]);
                }

                return ShowcaseOutcome::ok(null, $metrics, [
                    'Para añadir un post-procesador global basta con añadir su clase a `config/pdf.php → processors`.',
                ]);
            },
        );
    }

    private function testingDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            public function test_la_factura_se_descarga(): void
            {
                Pdf::fakeDriver()->reset();

                $this->get('/facturas/1/pdf')
                    ->assertOk()
                    ->assertHeader('content-type', 'application/pdf');

                $this->assertSame(
                    'documents.invoice',
                    Pdf::fakeDriver()->calls()[0]['description'],
                );
            }
            PHP;

        return $this->demo(
            slug: 'pruebas',
            title: 'Pruebas automáticas',
            summary: 'Con el motor `fake` las pruebas no renderizan nada: comprueban que la aplicación '
                .'pidió el documento correcto, sin depender de un motor real ni de su velocidad.',
            code: $code,
            variants: [
                $this->variant('llamada', 'Llamada registrada', 'Lo que la prueba puede afirmar.'),
                $this->variant('bytes', 'Bytes generados', 'El PDF mínimo que produce el motor fake.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $fake = $this->pdf->fakeDriver();
                $fake->reset();

                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->driver('fake')
                    ->render();

                $call = $fake->calls()[0] ?? [];

                $metrics = [
                    ['label' => 'Tipo de fuente', 'value' => (string) ($call['source'] ?? '—')],
                    ['label' => 'Descripción', 'value' => (string) ($call['description'] ?? '—')],
                    ['label' => 'Motor', 'value' => (string) ($call['driver'] ?? '—')],
                    ['label' => 'Papel', 'value' => (string) ($call['paper'] ?? '—')],
                    ['label' => 'Orientación', 'value' => (string) ($call['orientation'] ?? '—')],
                ];

                return ShowcaseOutcome::ok($result->bytes, array_merge($metrics, $this->metrics($result)), [
                    'El PDF empieza por %PDF- y termina en %%EOF: es válido, aunque sea mínimo.',
                ]);
            },
        );
    }

    /**
     * Resultado de la demo de pruebas, reutilizado por las pruebas del panel.
     */
    public function resultado(): PdfResult
    {
        return $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->driver('fake')
            ->render();
    }
}
