<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Documents\EngineProbe;
use App\Documents\PdfInspector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfDemoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_index_lists_the_available_engines(): void
    {
        $response = $this->get('/pdf');

        $response->assertOk();
        $response->assertSee('dompdf');
        $response->assertSee('mpdf');
        $response->assertSee(route('pdf.users'));
    }

    public function test_users_endpoint_returns_a_pdf_document(): void
    {
        User::factory()->count(2)->create();

        $response = $this->get('/pdf/users');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $body = (string) $response->getContent();

        $this->assertStringStartsWith('%PDF-', $body);
        $this->assertSame(1, PdfInspector::pageCount($body));
    }

    public function test_users_endpoint_renders_the_document_with_the_requested_driver(): void
    {
        $response = $this->get('/pdf/users?driver=mpdf');

        $response->assertOk();

        $body = (string) $response->getContent();

        $this->assertStringStartsWith('%PDF-', $body);
        $this->assertStringContainsString('595.28', PdfInspector::mediaBox($body));
    }

    public function test_users_endpoint_serves_the_document_inline_when_requested(): void
    {
        $response = $this->get('/pdf/users?inline=1');

        $response->assertOk();
        $response->assertHeader('content-disposition', 'inline; filename="usuarios-'.now()->format('Y-m-d').'.pdf"');
    }

    public function test_users_endpoint_uses_the_orientation_of_the_request(): void
    {
        $response = $this->get('/pdf/users?orientation=landscape');

        $response->assertOk();

        $body = (string) $response->getContent();

        $this->assertStringContainsString('841.89', PdfInspector::mediaBox($body));
    }

    public function test_users_endpoint_applies_the_requested_font(): void
    {
        User::factory()->count(2)->create();

        $response = $this->get('/pdf/users?font=DejaVu%20Serif');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $body = (string) $response->getContent();

        $this->assertStringStartsWith('%PDF-', $body);
        $this->assertSame(1, PdfInspector::pageCount($body));
    }

    public function test_users_endpoint_ignores_an_unknown_font(): void
    {
        $response = $this->get('/pdf/users?font=No%20Existe');

        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());
    }

    public function test_users_endpoint_explains_that_the_requested_driver_is_missing(): void
    {
        $response = $this->get('/pdf/users?driver=no-existe');

        $response->assertStatus(503);
        $response->assertSee('no-existe');
        $response->assertSee('no está disponible');
    }

    public function test_users_endpoint_renders_the_document_with_browsershot(): void
    {
        if (! app(EngineProbe::class)->probe('browsershot')->isOk()) {
            $this->markTestSkipped('El motor browsershot no está disponible en esta instalación.');
        }

        $response = $this->get('/pdf/users?driver=browsershot');

        $response->assertOk();

        $body = (string) $response->getContent();

        $this->assertStringStartsWith('%PDF-', $body);
        $this->assertSame(1, PdfInspector::pageCount($body));
    }

    public function test_inspect_endpoint_returns_the_metrics_of_the_render(): void
    {
        User::factory()->create();

        $response = $this->get('/pdf/inspect');

        $response->assertOk();

        $response->assertJsonPath('driver', 'dompdf');
        $response->assertJsonPath('source', 'blade');
        $response->assertJsonPath('pages', 1);
        $response->assertJsonStructure([
            'driver', 'source', 'filename', 'contentType', 'size',
            'durationMs', 'pages', 'mediaBox', 'sha256', 'warnings',
        ]);
    }
}
