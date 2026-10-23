<?php

declare(strict_types=1);

namespace Tests\Feature\Showcase;

use App\Documents\PdfInspector;
use App\Showcase\Processors\WatermarkProcessor;
use App\Showcase\Sources\PlantillaSource;
use App\Showcase\Support\DemoCertificate;
use App\Showcase\Support\SampleData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Twig\Environment;
use Vellum\Exceptions\DriverNotSupportedException;
use Vellum\Exceptions\InvalidConfigurationException;
use Vellum\Exceptions\SourceNotFoundException;
use Vellum\PdfManager;
use Vellum\Support\PdfResult;

/**
 * Pruebas de uso de la librería en la aplicación.
 *
 * Recorren las categorías de la API de Vellum (fuentes, motores, maquetación,
 * metadatos, post-procesado y entrega) comprobando el PDF que produce cada una.
 */
class VellumLibraryTest extends TestCase
{
    use RefreshDatabase;

    private PdfManager $pdf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdf = $this->app->make(PdfManager::class);
    }

    /* ── Fuentes ─────────────────────────────────────────────────── */

    public function test_it_renders_an_inline_html_source(): void
    {
        $result = $this->pdf->html('<h1>Hola</h1>')->render();

        $this->assertPdf($result);
        $this->assertSame('html', $result->sourceType);
    }

    public function test_it_renders_a_blade_view(): void
    {
        $result = $this->pdf->view('documents.invoice', [
            'factura' => SampleData::invoice(),
        ])->render();

        $this->assertPdf($result);
        $this->assertSame('blade', $result->sourceType);
    }

    public function test_it_renders_a_file_source(): void
    {
        $result = $this->pdf->file(resource_path('showcase/files/reporte.html'))->render();

        $this->assertPdf($result);
        $this->assertSame('file', $result->sourceType);
    }

    public function test_it_renders_a_twig_source(): void
    {
        if (! class_exists(Environment::class)) {
            $this->markTestSkipped('The twig/twig package is not installed.');
        }

        $result = $this->pdf->twig('showcase/templates/factura', SampleData::invoice())->render();

        $this->assertPdf($result);
        $this->assertSame('twig', $result->sourceType);
    }

    public function test_it_renders_a_custom_source(): void
    {
        $result = $this->pdf->from(new PlantillaSource('documents.invoice', [
            'factura' => SampleData::invoice(),
        ]))->render();

        $this->assertPdf($result);
        $this->assertSame('plantilla', $result->sourceType);
    }

    public function test_it_fails_when_the_source_does_not_exist(): void
    {
        $this->expectException(SourceNotFoundException::class);

        $this->pdf->view('documents.no-existe')->render();
    }

    /* ── Motores ─────────────────────────────────────────────────── */

    public function test_every_installed_driver_renders_the_same_document(): void
    {
        $motores = array_keys((array) config('pdf.drivers', []));
        $renderizados = [];

        foreach ($motores as $motor) {
            try {
                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->driver($motor)
                    ->render();
            } catch (DriverNotSupportedException) {
                continue;
            }

            $this->assertPdf($result);
            $this->assertSame($motor, $result->driver);

            $renderizados[] = $motor;
        }

        $this->assertContains('dompdf', $renderizados, 'El motor por defecto debería estar disponible.');
        $this->assertContains('fake', $renderizados, 'El motor de pruebas debería estar disponible.');
    }

    public function test_the_same_document_has_the_same_structure_across_renders(): void
    {
        $render = fn (): PdfResult => $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->metadata(['title' => 'Factura F-2026-0042'])
            ->render();

        $first = $render();
        $second = $render();

        $this->assertPdf($first);
        $this->assertPdf($second);

        $this->assertSame(1, PdfInspector::pageCount($first->bytes));
        $this->assertSame(PdfInspector::pageCount($first->bytes), PdfInspector::pageCount($second->bytes));
        $this->assertSame(PdfInspector::mediaBox($first->bytes), PdfInspector::mediaBox($second->bytes));
        $this->assertSame('Factura F-2026-0042', PdfInspector::title($first->bytes));
        $this->assertSame(PdfInspector::title($first->bytes), PdfInspector::title($second->bytes));
    }

    public function test_the_library_is_wired_into_the_container(): void
    {
        $this->assertInstanceOf(PdfManager::class, $this->app->make(PdfManager::class));
        $this->assertContains(
            config('pdf.default_driver'),
            array_keys((array) config('pdf.drivers', [])),
        );
    }

    public function test_the_fake_driver_records_the_call(): void
    {
        $fake = $this->pdf->fakeDriver();
        $fake->reset();

        $this->pdf->view('documents.invoice', [
            'factura' => SampleData::invoice(),
        ])->driver('fake')->render();

        $calls = $fake->calls();

        $this->assertCount(1, $calls);
        $this->assertSame('blade', $calls[0]['source']);
        $this->assertSame('documents.invoice', $calls[0]['description']);
        $this->assertSame('fake', $calls[0]['driver']);
    }

    public function test_it_fails_with_an_unknown_driver(): void
    {
        $this->expectException(DriverNotSupportedException::class);

        $this->pdf->html('<p>Hola</p>')->driver('no-existe')->render();
    }

    /* ── Maquetación ─────────────────────────────────────────────── */

    public function test_it_applies_the_requested_paper_size(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->paper('a5')
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('419.53', PdfInspector::mediaBox($result->bytes));
    }

    public function test_it_applies_the_requested_orientation(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->orientation('landscape')
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('841.89', PdfInspector::mediaBox($result->bytes));
    }

    public function test_it_rejects_an_unknown_paper_size(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->pdf->html('<p>Hola</p>')->paper('a9')->render();
    }

    /* ── Metadatos y conformidad ─────────────────────────────────── */

    public function test_it_writes_the_document_metadata(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->metadata([
                'title' => 'Factura F-2026-0042',
                'author' => 'Vellum Fábrica de PDF, S.L.',
                'subject' => 'Factura de prueba',
            ])
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('Factura F-2026-0042', $result->bytes);
        $this->assertStringContainsString('Vellum Fábrica de PDF, S.L.', $result->bytes);
        $this->assertStringContainsString('Factura de prueba', $result->bytes);
    }

    public function test_it_writes_custom_properties_in_the_xmp_packet(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->xmpNamespace('vellum', 'https://laravel-vellum.test/ns/1.0')
            ->xmp(['vellum:reference' => 'REF-0042'])
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('REF-0042', $result->bytes);
        $this->assertStringContainsString('https://laravel-vellum.test/ns/1.0', $result->bytes);
    }

    public function test_it_writes_the_xmp_packet(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->xmp(['dc:title' => 'Factura F-2026-0042'])
            ->xmpNamespace('vellum', 'https://laravel-vellum.test/ns/1.0')
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('/Metadata', $result->bytes);
    }

    public function test_it_marks_the_document_as_pdfa(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->pdfa()
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('pdfaid:part', $result->bytes);
    }

    public function test_it_writes_the_output_intent_with_an_icc_profile(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->pdfa(resource_path('pdf/srgb.icc'))
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('pdfaid:part', $result->bytes);
        $this->assertStringContainsString('/OutputIntent', $result->bytes);
        $this->assertStringContainsString('GTS_PDFA1', $result->bytes);
    }

    public function test_it_embeds_a_factur_x_document(): void
    {
        $xml = base_path('resources/showcase/factur-x.xml');

        $this->assertFileExists($xml);

        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->zugferd($xml, ['document_type' => 'INVOICE'])
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('factur-x.xml', $result->bytes);
        $this->assertStringContainsString('pdfaid:part', $result->bytes);
    }

    /* ── Post-procesado ──────────────────────────────────────────── */

    public function test_it_embeds_a_file(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->embedFile(base_path('resources/showcase/factur-x.xml'), 'factur-x.xml')
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('/EmbeddedFile', $result->bytes);
    }

    public function test_it_merges_documents(): void
    {
        $anexo = storage_path('app/pdf-showcase/anexo-test.pdf');

        if (! is_dir(dirname($anexo))) {
            mkdir(dirname($anexo), 0o755, true);
        }

        $this->pdf->html('<h1>Anexo</h1>')->save($anexo);

        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->merge([$anexo])
            ->render();

        $this->assertPdf($result);
        $this->assertSame(2, PdfInspector::pageCount($result->bytes));

        @unlink($anexo);
    }

    public function test_it_signs_the_document(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->sign(app(DemoCertificate::class)->path(), '', ['name' => 'Firma de prueba'])
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('/SubFilter /adbe.pkcs7.detached', $result->bytes);
    }

    public function test_it_applies_a_custom_processor(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->process(new WatermarkProcessor('CONFIDENCIAL'))
            ->render();

        $this->assertPdf($result);
        $this->assertStringContainsString('CONFIDENCIAL', $result->bytes);
    }

    /* ── Entrega ─────────────────────────────────────────────────── */

    public function test_it_returns_a_streamed_response(): void
    {
        $response = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->filename('factura.pdf')
            ->stream();

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertSame('inline; filename="factura.pdf"', $response->headers->get('content-disposition'));
    }

    public function test_it_returns_a_download_response(): void
    {
        $response = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->download('factura.pdf');

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertSame('attachment; filename="factura.pdf"', $response->headers->get('content-disposition'));
    }

    public function test_it_saves_the_document_to_disk(): void
    {
        $path = storage_path('app/pdf-showcase/documento-test.pdf');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0o755, true);
        }

        $ruta = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->save($path);

        $this->assertSame($path, $ruta);
        $this->assertFileExists($path);
        $this->assertStringStartsWith('%PDF-', (string) file_get_contents($path));

        @unlink($path);
    }

    public function test_it_exposes_the_result_metrics(): void
    {
        $result = $this->pdf
            ->view('documents.invoice', ['factura' => SampleData::invoice()])
            ->render();

        $this->assertSame('application/pdf', $result->contentType());
        $this->assertGreaterThan(0, $result->size());
        $this->assertSame(64, strlen($result->sha256()));
        $this->assertSame(32, strlen($result->md5()));
        $this->assertNotEmpty($result->toBase64());
        $this->assertIsArray($result->warnings);
    }

    /**
     * Comprueba que el resultado es un PDF válido.
     */
    private function assertPdf(PdfResult $result): void
    {
        $this->assertStringStartsWith('%PDF-', $result->bytes);
        $this->assertStringContainsString('%%EOF', $result->bytes);
        $this->assertGreaterThan(0, PdfInspector::pageCount($result->bytes));
    }
}
