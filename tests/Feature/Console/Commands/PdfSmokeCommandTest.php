<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class PdfSmokeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_every_document_with_dompdf(): void
    {
        $this->artisan('pdf:smoke', ['--driver' => ['dompdf']])
            ->expectsOutputToContain('OK')
            ->expectsOutputToContain('Los 3 renderizados fueron correctos.')
            ->assertSuccessful();
    }

    public function test_it_renders_every_document_with_the_fake_driver(): void
    {
        $this->artisan('pdf:smoke', ['--driver' => ['fake']])
            ->expectsOutputToContain('OK')
            ->assertSuccessful();
    }

    public function test_it_saves_the_generated_documents(): void
    {
        $directory = storage_path('app/pdf-smoke');

        File::deleteDirectory($directory);

        try {
            $this->artisan('pdf:smoke', ['--driver' => ['fake'], '--save' => true])
                ->assertSuccessful();

            $this->assertDirectoryExists($directory);

            foreach (['factura', 'informe', 'html'] as $sample) {
                $path = $directory.'/'.$sample.'-fake.pdf';

                $this->assertFileExists($path);
                $this->assertStringStartsWith('%PDF-', (string) file_get_contents($path));
            }
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_it_rejects_an_unknown_driver(): void
    {
        $this->artisan('pdf:smoke', ['--driver' => ['no-existe']])
            ->expectsOutputToContain('DESCONOCIDO')
            ->assertFailed();
    }
}
