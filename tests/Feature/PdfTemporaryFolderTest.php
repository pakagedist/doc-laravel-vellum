<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * mPDF escribe sus temporales y su caché de fuentes en un directorio
 * escribible: por defecto apunta dentro de `vendor/`, que en producción no lo
 * es y provoca el error "Temporary files directory ... is not writable".
 */
class PdfTemporaryFolderTest extends TestCase
{
    public function test_the_mpdf_temporary_folder_is_a_writable_folder_outside_the_vendor_directory(): void
    {
        $folder = config('pdf.drivers.mpdf.temp_folder');

        $this->assertIsString($folder);
        $this->assertStringStartsWith(storage_path(), $folder);
        $this->assertDirectoryExists($folder);
        $this->assertDirectoryIsWritable($folder);
    }
}
