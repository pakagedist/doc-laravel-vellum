<?php

declare(strict_types=1);

namespace Tests\Feature\Showcase;

use App\Showcase\Fonts\FontCatalog;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * El catálogo de fuentes solo publica lo que la instalación puede usar.
 */
class FontCatalogTest extends TestCase
{
    public function test_the_catalog_is_not_empty_and_every_family_has_a_driver(): void
    {
        $catalog = new FontCatalog;
        $families = $catalog->all();

        $this->assertNotEmpty($families);

        foreach ($families as $font) {
            $this->assertNotSame('', $font->family);
            $this->assertNotEmpty($font->drivers, 'La familia «'.$font->family.'» no tiene ningún motor.');
            $this->assertNotNull($font->preferredDriver());
        }
    }

    public function test_the_keys_are_unique_and_can_be_looked_up(): void
    {
        $catalog = new FontCatalog;
        $keys = array_map(fn ($font): string => $font->key, $catalog->all());

        $this->assertSame($keys, array_values(array_unique($keys)));

        foreach ($keys as $key) {
            $this->assertSame($key, $catalog->find($key)?->key);
        }

        $this->assertNull($catalog->find('no-existe'));
    }

    public function test_dejavu_sans_is_available_in_the_three_engines(): void
    {
        $font = (new FontCatalog)->find('dejavu-sans');

        $this->assertNotNull($font);
        $this->assertTrue($font->supports('dompdf'));
        $this->assertTrue($font->supports('mpdf'));
        $this->assertTrue($font->supports('browsershot'));
        $this->assertSame('browsershot', $font->preferredDriver());
    }

    public function test_the_core_fonts_are_preferred_in_dompdf(): void
    {
        $font = (new FontCatalog)->find('helvetica');

        $this->assertNotNull($font);
        $this->assertSame('dompdf', $font->preferredDriver());
        $this->assertNull($font->fileName());
    }

    public function test_the_mpdf_only_families_are_marked_as_such(): void
    {
        $font = (new FontCatalog)->find('dejavu-sans-condensed');

        $this->assertNotNull($font);
        $this->assertSame(['mpdf'], $font->drivers);
        $this->assertSame('mPDF', $font->originLabel());
    }

    public function test_the_totals_add_up_to_the_published_families(): void
    {
        $catalog = new FontCatalog;
        $totals = $catalog->totals();

        $this->assertSame(count($catalog->all()), $totals['total']);
        $this->assertArrayHasKey('sistema', $totals);
        $this->assertArrayHasKey('core', $totals);
    }

    public function test_the_catalog_filters_the_families_by_driver(): void
    {
        $catalog = new FontCatalog;

        $dompdf = $catalog->forDriver('dompdf');

        $this->assertNotEmpty($dompdf);

        foreach ($dompdf as $font) {
            $this->assertTrue($font->supports('dompdf'));
        }

        $this->assertSame([], $catalog->forDriver('no-existe'));
    }

    public function test_the_catalog_knows_the_fonts_bundled_with_the_engines(): void
    {
        $bundled = (new FontCatalog)->bundled();

        $this->assertGreaterThan(0, $bundled['mpdf']);
        $this->assertGreaterThanOrEqual(3, $bundled['dompdf']);
    }

    public function test_the_catalog_includes_other_scripts_from_mpdf(): void
    {
        $catalog = new FontCatalog;

        $scripts = [
            'xb-riyaz' => 'Árabe',
            'taamey-david-clm' => 'Hebreo',
            'garuda' => 'Tailandés',
            'sun-exta' => 'CJK',
        ];

        foreach ($scripts as $key => $script) {
            $font = $catalog->find($key);

            $this->assertNotNull($font, 'Falta la familia «'.$key.'».');
            $this->assertSame($script, $font->script);
            $this->assertTrue($font->supports('mpdf'));
        }
    }

    public function test_the_latin_filter_leaves_out_the_other_scripts(): void
    {
        $catalog = new FontCatalog;
        $latin = $catalog->latin();

        $this->assertNotEmpty($latin);
        $this->assertSame($catalog->totals()['latino'], count($latin));

        foreach ($latin as $font) {
            $this->assertContains($font->script, ['Latino', 'Multiscript']);
        }

        $keys = array_map(fn ($font): string => $font->key, $latin);

        $this->assertContains('dejavu-sans', $keys);
        $this->assertNotContains('xb-riyaz', $keys);
    }

    public function test_a_ttf_dropped_in_the_project_folder_is_detected_after_flushing_the_cache(): void
    {
        $directory = resource_path('fonts');
        $file = $directory.'/VellumTest-Regular.ttf';

        File::ensureDirectoryExists($directory);
        copy(base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'), $file);

        try {
            (new FontCatalog)->flush();

            $font = (new FontCatalog)->find('vellum-test');

            $this->assertNotNull($font);
            $this->assertSame('Proyecto', $font->originLabel());
            $this->assertTrue($font->supports('dompdf'));
            $this->assertSame('Vellum Test', $font->family);
        } finally {
            @unlink($file);

            if (count(scandir($directory)) === 2) {
                @rmdir($directory);
            }

            (new FontCatalog)->flush();
        }
    }
}
