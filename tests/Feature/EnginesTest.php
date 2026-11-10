<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Documents\ChromeLauncher;
use App\Documents\EngineProbe;
use App\Documents\Engines;
use App\Documents\EngineStatus;
use App\Showcase\ShowcaseCatalog;
use Tests\TestCase;

/**
 * La aplicación solo ofrece los motores que puede usar de verdad.
 *
 * dompdf, mPDF y el motor de pruebas son PHP puro: siempre están. Chromium
 * depende de que haya un navegador disponible, que puede estar en el sistema,
 * en la caché de Puppeteer o dentro de la propia app (`.browsers/`).
 */
class EnginesTest extends TestCase
{
    public function test_the_pure_php_engines_are_always_available(): void
    {
        $engines = $this->app->make(Engines::class);

        $this->assertTrue($engines->isAvailable('dompdf'));
        $this->assertTrue($engines->isAvailable('mpdf'));
        $this->assertTrue($engines->isAvailable('fake'));
        $this->assertFalse($engines->isAvailable('no-existe'));
    }

    public function test_browsershot_is_only_available_when_there_is_a_browser(): void
    {
        $engines = $this->app->make(Engines::class);

        $this->assertSame($engines->chromePath() !== null, $engines->isAvailable('browsershot'));

        if ($engines->chromePath() !== null) {
            $this->assertFileExists($engines->chromePath());
        }
    }

    public function test_the_fallback_is_always_an_available_engine(): void
    {
        $engines = $this->app->make(Engines::class);

        $this->assertTrue($engines->isAvailable($engines->fallback('browsershot')));
    }

    public function test_the_panel_only_offers_the_engines_that_can_run(): void
    {
        $engines = $this->app->make(Engines::class);
        $demo = $this->app->make(ShowcaseCatalog::class)->demo('motores', 'comparativa');

        $this->assertNotNull($demo);

        $keys = array_map(fn ($variant): string => $variant->key, $demo->variants);

        $this->assertContains('dompdf', $keys);
        $this->assertContains('mpdf', $keys);
        $this->assertSame(
            $engines->isAvailable('browsershot'),
            in_array('browsershot', $keys, true),
        );
    }

    public function test_the_font_catalog_only_publishes_families_that_can_be_rendered(): void
    {
        $engines = $this->app->make(Engines::class);
        $demo = $this->app->make(ShowcaseCatalog::class)->demo('tipografias', 'motores');

        $this->assertNotNull($demo);

        foreach ($demo->variants as $variant) {
            $this->assertTrue($engines->isAvailable($variant->key));
        }
    }

    public function test_an_engine_that_cannot_run_is_reported_as_missing_instead_of_failing(): void
    {
        $status = $this->app->make(EngineProbe::class)->probe('no-existe');

        $this->assertSame(EngineStatus::MISSING, $status->state);
        $this->assertStringContainsString('no está disponible', $status->message);
        $this->assertFalse($status->isOk());
    }

    public function test_the_browsershot_driver_points_to_the_browser_of_the_installation(): void
    {
        $engines = $this->app->make(Engines::class);
        $chrome = $engines->chromePath();

        if ($chrome === null) {
            $this->assertNull(config('pdf.drivers.browsershot.options.chrome_path'));

            return;
        }

        $path = config('pdf.drivers.browsershot.options.chrome_path');

        $this->assertNotNull($path);
        $this->assertFileExists($path);

        if ($engines->runsAsRoot()) {
            $this->assertStringContainsString('--no-sandbox', (string) file_get_contents($path));
        } else {
            $this->assertSame($chrome, $path);
        }
    }

    public function test_the_launcher_adds_the_arguments_that_root_needs(): void
    {
        $launcher = $this->app->make(ChromeLauncher::class);

        $this->assertSame('/usr/bin/chromium', $launcher->path('/usr/bin/chromium', false));

        $path = $launcher->path('/usr/bin/chromium', true);

        $this->assertNotSame('/usr/bin/chromium', $path);
        $this->assertFileExists($path);

        $script = (string) file_get_contents($path);

        $this->assertStringContainsString("exec '/usr/bin/chromium'", $script);
        $this->assertStringContainsString('--no-sandbox', $script);
        $this->assertStringContainsString('--disable-dev-shm-usage', $script);
    }
}
