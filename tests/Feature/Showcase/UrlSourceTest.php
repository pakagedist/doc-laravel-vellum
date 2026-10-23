<?php

declare(strict_types=1);

namespace Tests\Feature\Showcase;

use Tests\Support\StartsHttpServer;
use Tests\TestCase;

/**
 * La demostración de fuente por URL necesita un servidor HTTP de verdad.
 *
 * Se levanta el servidor PHP integrado sobre `public/`, igual que haría el
 * servidor de producción, y se apunta la demostración a la página estática
 * que publica la aplicación.
 */
class UrlSourceTest extends TestCase
{
    use StartsHttpServer;

    protected function tearDown(): void
    {
        $this->stopHttpServer();

        parent::tearDown();
    }

    public function test_the_url_source_downloads_the_published_page(): void
    {
        config()->set('showcase.remote_url', $this->startHttpServer(public_path()).'/showcase/remoto.html');

        $response = $this->get(route('pdf.demo.variant', ['fuentes', 'url', 'remota']));

        $response->assertOk();

        // La vista previa solo aparece cuando el PDF se ha generado de verdad.
        $response->assertSee('class="preview"', false);
        $response->assertDontSee('no se puede ejecutar aquí');
    }

    public function test_the_url_source_reports_a_missing_page(): void
    {
        config()->set('showcase.remote_url', $this->startHttpServer(public_path()).'/no-existe.html');

        $response = $this->get(route('pdf.demo.variant', ['fuentes', 'url', 'remota']));

        $response->assertOk();
        $response->assertSee('no se puede ejecutar aquí');
    }

    public function test_the_url_source_reports_a_server_that_does_not_answer(): void
    {
        config()->set('showcase.remote_error_url', 'http://127.0.0.1:1/vellum-no-existe.html');

        $response = $this->get(route('pdf.demo.variant', ['fuentes', 'url', 'error']));

        $response->assertOk();
        $response->assertSee('no se puede ejecutar aquí');
        $response->assertDontSee('La demostración ha fallado');
    }
}
