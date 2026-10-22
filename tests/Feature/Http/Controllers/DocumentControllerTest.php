<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Documents\PdfInspector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_endpoint_returns_a_pdf_document(): void
    {
        User::factory()->count(3)->create();

        $response = $this->get('/documents/users');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $body = (string) $response->getContent();

        $this->assertStringStartsWith('%PDF-', $body);
        $this->assertSame(1, PdfInspector::pageCount($body));
    }

    public function test_users_endpoint_uses_the_paper_configured_in_the_application(): void
    {
        User::factory()->create();

        $body = (string) $this->get('/documents/users')->getContent();

        // A4 vertical: 595,28 x 841,89 puntos.
        $this->assertStringContainsString('595.28', PdfInspector::mediaBox($body));
        $this->assertStringContainsString('841.89', PdfInspector::mediaBox($body));
    }

    public function test_users_endpoint_names_the_file_after_the_generation_date(): void
    {
        $response = $this->get('/documents/users');

        $response->assertHeader(
            'content-disposition',
            'attachment; filename="usuarios-'.now()->format('Y-m-d').'.pdf"',
        );
    }
}
