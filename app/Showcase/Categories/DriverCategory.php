<?php

declare(strict_types=1);

namespace App\Showcase\Categories;

use App\Showcase\ShowcaseCategory;
use App\Showcase\ShowcaseCategoryBuilder;
use App\Showcase\ShowcaseDemo;
use App\Showcase\ShowcaseOutcome;
use App\Showcase\ShowcaseVariant;
use App\Showcase\Support\SampleData;
use Vellum\Contracts\Capability;
use Vellum\Drivers\DriverRegistry;
use Vellum\Exceptions\DriverNotSupportedException;
use Vellum\PdfManager;

/**
 * Los motores que hay disponibles y cómo se comparan entre ellos.
 */
final class DriverCategory extends ShowcaseCategoryBuilder
{
    public function __construct(
        private readonly PdfManager $pdf,
        private readonly DriverRegistry $drivers,
    ) {}

    public function build(): ShowcaseCategory
    {
        return new ShowcaseCategory(
            slug: 'motores',
            title: 'Motores de render',
            summary: 'El motor es intercambiable: DOMPDF (PHP puro), mPDF (PHP rápido), Chromium '
                .'(HTML/CSS completo) y el motor `fake` para pruebas. El mismo código produce el mismo '
                .'documento con cualquiera de ellos.',
            demos: [
                $this->comparisonDemo(),
                $this->capabilitiesDemo(),
                $this->fakeDemo(),
            ],
        );
    }

    private function comparisonDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // El motor se elige por documento...
            $result = Pdf::view('documents.invoice', $data)
                ->driver('mpdf')
                ->render();

            // ...o de forma global en config/pdf.php (PDF_DRIVER).
            PHP;

        return $this->demo(
            slug: 'comparativa',
            title: 'El mismo documento, cuatro motores',
            summary: 'La plantilla no cambia: solo el motor. Se puede comparar peso, tiempo y diferencias '
                .'de maquetación entre DOMPDF, mPDF, Chromium y el motor de pruebas.',
            code: $code,
            variants: [
                $this->variant('dompdf', 'DOMPDF', 'Motor por defecto. HTML/CSS 2.1 en PHP puro.'),
                $this->variant('mpdf', 'mPDF', 'Más rápido y con mejor soporte de Unicode.'),
                $this->variant('browsershot', 'Chromium (Browsershot)', 'HTML/CSS moderno vía Node + Puppeteer.'),
                $this->variant('fake', 'Fake (pruebas)', 'No renderiza: devuelve un PDF mínimo y registra la llamada.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                try {
                    $result = $this->pdf
                        ->view('documents.invoice', ['factura' => SampleData::invoice()])
                        ->driver($variant->key)
                        ->render();
                } catch (DriverNotSupportedException $exception) {
                    return ShowcaseOutcome::unavailable($exception->getMessage(), [
                        ['label' => 'Activación', 'value' => 'composer require spatie/browsershot && npm install puppeteer'],
                    ]);
                }

                return ShowcaseOutcome::ok($result->bytes, $this->metrics($result), [
                    'El motor «'.$variant->key.'» ha resuelto la misma vista Blade.',
                ]);
            },
        );
    }

    private function capabilitiesDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Drivers\DriverRegistry;

            // El registro de motores es un singleton del contenedor.
            $drivers = app(DriverRegistry::class)->all();

            foreach ($drivers as $name => $driver) {
                $driver->name();          // 'dompdf'
                $driver->capabilities();  // ['xmp', 'pdfa', 'merge', ...]
                $driver->supports(Capability::SIGNATURE);
            }
            PHP;

        return $this->demo(
            slug: 'capacidades',
            title: 'Capacidades de cada motor',
            summary: 'El post-procesado (XMP, PDF/A, adjuntos, unión, firma) es genérico y funciona con '
                .'todos los motores, pero cada motor declara las capacidades que anuncia.',
            code: $code,
            variants: [
                $this->variant('registro', 'Matriz de capacidades', 'Qué admite cada motor registrado.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $metrics = [];

                foreach ($this->drivers->all() as $name => $driver) {
                    $capabilities = array_map(
                        static fn (string $capability): string => match ($capability) {
                            Capability::XMP => 'XMP',
                            Capability::PDFA => 'PDF/A',
                            Capability::EMBEDDED_FILES => 'adjuntos',
                            Capability::MERGE => 'unión',
                            Capability::SIGNATURE => 'firma',
                            Capability::METADATA => 'metadatos',
                            default => $capability,
                        },
                        $driver->capabilities(),
                    );

                    $metrics[] = [
                        'label' => $name,
                        'value' => $capabilities === [] ? 'sin capacidades' : implode(' · ', $capabilities),
                    ];
                }

                return ShowcaseOutcome::ok(null, $metrics, [
                    'Motores registrados: '.count($this->drivers->all()).'.',
                    'Los nombres salen de `Driver::name()`; el registro es un singleton del contenedor.',
                ]);
            },
        );
    }

    private function fakeDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // El motor fake genera un PDF mínimo y registra la llamada.
            Pdf::fakeDriver()->reset();

            Pdf::view('documents.invoice', $data)->driver('fake')->render();

            $calls = Pdf::fakeDriver()->calls();
            // [['source' => 'blade', 'description' => 'documents.invoice', ...]]
            PHP;

        return $this->demo(
            slug: 'fake',
            title: 'Motor de pruebas',
            summary: 'Para las pruebas automaticas: no renderiza nada, devuelve un PDF válido mínimo y '
                .'guarda qué se pidió, para poder afirmar sobre la llamada sin depender de un motor real.',
            code: $code,
            variants: [
                $this->variant('llamadas', 'Llamadas registradas', 'Qué ha pedido la aplicación al motor.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $fake = $this->pdf->fakeDriver();
                $fake->reset();

                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->driver('fake')
                    ->render();

                $calls = [];

                foreach ($fake->calls() as $call) {
                    $calls[] = [
                        'label' => (string) $call['description'],
                        'value' => 'fuente='.$call['source']
                            .' · motor='.$call['driver']
                            .' · papel='.$call['paper']
                            .' · orientación='.$call['orientation'],
                    ];
                }

                $metrics = array_merge($this->metrics($result), $calls);

                return ShowcaseOutcome::ok($result->bytes, $metrics, [
                    'El PDF del motor fake es mínimo, pero válido (%PDF-… y %%EOF).',
                    'Las llamadas se acumulan en el propio driver: hay que llamar a reset() entre pruebas.',
                ]);
            },
        );
    }
}
