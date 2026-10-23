<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Showcase\ShowcaseCatalog;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\StartsHttpServer;
use Tests\TestCase;

/**
 * Pruebas del panel de demostración: todas las pantallas tienen que responder
 * y todas las demostraciones tienen que ejecutar de verdad.
 */
class ShowcaseControllerTest extends TestCase
{
    use StartsHttpServer;

    protected function setUp(): void
    {
        parent::setUp();

        // La demostración de fuente por URL descarga una página real del
        // servidor local, que sirve el mismo `public/` que se publica.
        config()->set(
            'showcase.remote_url',
            $this->startHttpServer(public_path()).'/showcase/remoto.html',
        );
    }

    protected function tearDown(): void
    {
        $this->stopHttpServer();

        parent::tearDown();
    }

    public function test_the_home_page_shows_the_whole_catalog(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Catálogo completo');
        $response->assertSee('Motores disponibles');
        $response->assertSee('Qué hay en esta aplicación');

        foreach (app(ShowcaseCatalog::class)->categories() as $category) {
            $response->assertSee($category->title);

            foreach ($category->demos as $demo) {
                $response->assertSee($demo->title);
            }
        }
    }

    public function test_the_home_page_lists_the_application_routes(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('/documents/users');
        $response->assertSee('showcase.home');
    }

    public function test_the_panel_index_shows_the_engines(): void
    {
        $response = $this->get(route('pdf.index'));

        $response->assertOk();
        $response->assertSee('dompdf');
        $response->assertSee('mpdf');
        $response->assertSee('Motores detectados');
    }

    public function test_every_category_page_is_available(): void
    {
        foreach (app(ShowcaseCatalog::class)->categories() as $category) {
            $response = $this->get(route('pdf.category', $category->slug));

            $this->assertTrue(
                $response->isOk(),
                'La categoría «'.$category->slug.'» debería responder 200.',
            );
            $response->assertSee($category->title);
        }
    }

    public function test_every_demo_and_variant_is_available(): void
    {
        foreach (app(ShowcaseCatalog::class)->categories() as $category) {
            foreach ($category->demos as $demo) {
                foreach ($demo->variants as $variant) {
                    $response = $this->get(route('pdf.demo.variant', [
                        $category->slug,
                        $demo->slug,
                        $variant->key,
                    ]));

                    $this->assertTrue(
                        $response->isOk(),
                        sprintf(
                            'La demostración «%s/%s/%s» debería responder 200.',
                            $category->slug,
                            $demo->slug,
                            $variant->key,
                        ),
                    );
                    $response->assertSee($demo->title);
                    $response->assertDontSee('La demostración ha fallado');
                }
            }
        }
    }

    public function test_the_demo_without_a_variant_uses_the_first_one(): void
    {
        $response = $this->get(route('pdf.demo', ['fuentes', 'html']));

        $response->assertOk();
        $response->assertSee('Encabezado y párrafo');
    }

    public function test_every_demo_that_produces_a_pdf_serves_it_inline(): void
    {
        foreach (app(ShowcaseCatalog::class)->categories() as $category) {
            foreach ($category->demos as $demo) {
                foreach ($demo->variants as $variant) {
                    $response = $this->get(route('pdf.preview', [
                        $category->slug,
                        $demo->slug,
                        $variant->key,
                    ]));

                    if ($response->status() === Response::HTTP_SERVICE_UNAVAILABLE) {
                        continue;
                    }

                    $this->assertTrue(
                        $response->isOk(),
                        sprintf(
                            'La vista previa de «%s/%s/%s» debería responder 200.',
                            $category->slug,
                            $demo->slug,
                            $variant->key,
                        ),
                    );

                    $this->assertSame('application/pdf', $response->headers->get('content-type'));
                    $this->assertStringStartsWith('%PDF-', $response->getContent());
                }
            }
        }
    }

    public function test_the_download_route_sends_the_file_as_an_attachment(): void
    {
        $response = $this->get(route('pdf.download', ['fuentes', 'html', 'basico']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename="html-basico.pdf"');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_the_json_route_returns_the_document_in_base64(): void
    {
        $response = $this->get(route('pdf.showcase.json', ['salida', 'json', 'factura']));

        $response->assertOk();
        $response->assertJsonStructure(['file', 'size', 'sha256']);

        $decodificado = base64_decode((string) $response->json('file'), true);

        $this->assertIsString($decodificado);
        $this->assertStringStartsWith('%PDF-', $decodificado);
    }

    public function test_the_remote_page_is_published_as_a_static_file(): void
    {
        $archivo = public_path('showcase/remoto.html');

        $this->assertFileExists($archivo);
        $this->assertStringContainsString('Documento remoto', (string) file_get_contents($archivo));
    }

    public function test_the_panel_uses_the_package_name_in_the_title(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Inicio · laravel-vellum</title>', false);

        $this->get(route('pdf.index'))
            ->assertOk()
            ->assertSee('<title>Índice del panel · laravel-vellum</title>', false);
    }

    public function test_the_navigation_shows_where_the_visitor_is(): void
    {
        $this->get(route('pdf.category', 'fuentes'))
            ->assertOk()
            ->assertSee('class="nav-title active"', false);

        $this->get(route('pdf.demo.variant', ['documentos', 'firmar', 'firmado']))
            ->assertOk()
            ->assertSee('class="nav-title is-parent"', false)
            ->assertSee('class="nav-link active"', false);
    }

    public function test_the_font_category_shows_the_catalog_and_the_application_guide(): void
    {
        $response = $this->get(route('pdf.category', 'tipografias'));

        $response->assertOk();
        $response->assertSee('Catálogo de fuentes');
        $response->assertSee('Cómo aplicarlo en la app');
        $response->assertSee('class="font-sample"', false);
        $response->assertSee('DejaVu Sans Condensed');
        $response->assertSee('fontFile');
    }

    public function test_the_font_demo_renders_the_specimen_with_the_requested_family(): void
    {
        $response = $this->get(route('pdf.demo.variant', ['tipografias', 'familias', 'nimbus-sans']));

        $response->assertOk();
        $response->assertSee('Nimbus Sans');
        $response->assertSee('Familia');
        $response->assertDontSee('La demostración ha fallado');

        $this->get(route('pdf.preview', ['tipografias', 'familias', 'nimbus-sans']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_the_embedding_demo_explains_where_it_does_not_work(): void
    {
        $this->get(route('pdf.demo.variant', ['tipografias', 'incrustar', 'mpdf']))
            ->assertOk()
            ->assertSee('Esta demostración no se puede ejecutar aquí')
            ->assertSee('ttfonts');

        $this->get(route('pdf.demo.variant', ['tipografias', 'incrustar', 'browsershot']))
            ->assertOk()
            ->assertSee('Esta demostración no se puede ejecutar aquí')
            ->assertSee('file://');
    }

    public function test_an_unknown_category_returns_not_found(): void
    {
        $this->get('/pdf/no-existe')->assertNotFound();
    }

    public function test_an_unknown_demo_returns_not_found(): void
    {
        $this->get('/pdf/fuentes/no-existe')->assertNotFound();
    }
}
