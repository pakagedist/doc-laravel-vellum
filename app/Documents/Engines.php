<?php

declare(strict_types=1);

namespace App\Documents;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Los motores de PDF que esta instalación puede usar de verdad.
 *
 * dompdf, mPDF y el motor de pruebas son PHP puro: siempre están. Chromium
 * (el motor de browsershot) necesita un navegador, que puede ser el del
 * sistema, el de la caché de Puppeteer o la copia que se incluye dentro de la
 * propia aplicación (`.browsers/`, creada durante el build con
 * `npm run browsers`).
 *
 * Con esta información el panel puede ofrecer solo lo que funciona y evitar
 * errores en un despliegue donde Chromium no esté instalado.
 */
final class Engines
{
    /**
     * Motores que no necesitan nada fuera de la aplicación.
     */
    private const BUNDLED = ['dompdf', 'mpdf', 'fake'];

    /**
     * Nombres de los ejecutables de Chromium en el sistema.
     */
    private const SYSTEM = [
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/snap/bin/chromium',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    ];

    /**
     * Patrones del navegador dentro de un directorio de Chrome for Testing.
     */
    private const PATTERNS = [
        'chrome/*/chrome-linux64/chrome',
        'chrome/*/chrome-mac*/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing',
        'chrome/*/chrome-win64/chrome.exe',
    ];

    /**
     * Tiempo que se recuerda qué navegador sirve, en segundos.
     */
    private const CACHE_TTL = 3600;

    private ?string $chrome = null;

    private bool $chromeResolved = false;

    /**
     * Los motores disponibles en esta instalación.
     *
     * @return list<string>
     */
    public function available(): array
    {
        $available = self::BUNDLED;

        if ($this->chromePath() !== null) {
            $available[] = 'browsershot';
        }

        return $available;
    }

    /**
     * ¿Se puede usar ese motor aquí?
     */
    public function isAvailable(string $driver): bool
    {
        return in_array($driver, $this->available(), true);
    }

    /**
     * El motor que se usa cuando el pedido no está disponible.
     */
    public function fallback(string $driver): string
    {
        $default = (string) config('pdf.default_driver', 'dompdf');

        return $this->isAvailable($default) ? $default : 'dompdf';
    }

    /**
     * Por qué no se puede usar un motor.
     */
    public function reason(string $driver): string
    {
        if ($driver !== 'browsershot') {
            return 'El motor «'.$driver.'» no está disponible en esta aplicación.';
        }

        return 'Chromium no está disponible. Se puede incluir dentro de la app durante el build '
            .'con «npm run browsers».';
    }

    /**
     * Ruta del navegador que usaría browsershot, si hay alguno.
     *
     * Solo se devuelve si el navegador existe y arranca: en una imagen de
     * contenedor puede faltar alguna biblioteca y entonces el motor fallaría
     * al renderizar.
     */
    public function chromePath(): ?string
    {
        if ($this->chromeResolved) {
            return $this->chrome;
        }

        $this->chromeResolved = true;

        $candidates = $this->candidates();
        $cached = $this->cached($candidates);

        if ($cached !== null) {
            return $this->chrome = $cached;
        }

        foreach ($candidates as $candidate) {
            if ($this->canRun($candidate)) {
                $this->remember($candidates, $candidate);

                return $this->chrome = $candidate;
            }
        }

        $this->remember($candidates, null);

        return $this->chrome = null;
    }

    /**
     * ¿El proceso se ejecuta como root? Chrome no arranca así sin `--no-sandbox`.
     */
    public function runsAsRoot(): bool
    {
        return function_exists('posix_geteuid') && posix_geteuid() === 0;
    }

    /**
     * Los navegadores candidatos, en orden de preferencia.
     *
     * @return list<string>
     */
    private function candidates(): array
    {
        $candidates = [];

        $env = getenv('BROWSERSHOT_CHROME_PATH');

        if (is_string($env) && $env !== '' && is_file($env)) {
            $candidates[] = $env;
        }

        // El Chromium que se incluye dentro de la propia aplicación.
        $candidates = array_merge($candidates, $this->globBrowsers(base_path('.browsers')));

        foreach (self::SYSTEM as $browser) {
            if (is_file($browser)) {
                $candidates[] = $browser;
            }
        }

        $home = getenv('HOME');

        if (is_string($home) && $home !== '') {
            $candidates = array_merge($candidates, $this->globBrowsers($home.'/.cache/puppeteer'));
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Comprueba que el navegador se puede ejecutar en esta máquina.
     */
    private function canRun(string $browser): bool
    {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $process = @proc_open([$browser, '--version'], $descriptors, $pipes);

        if (! is_resource($process)) {
            return false;
        }

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process) === 0 && is_string($output) && $output !== '';
    }

    /**
     * @param  list<string>  $candidates
     */
    private function cached(array $candidates): ?string
    {
        try {
            $value = Cache::store('file')->get($this->cacheKey($candidates));
        } catch (Throwable) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        return $value === '' ? null : (string) $value;
    }

    /**
     * @param  list<string>  $candidates
     */
    private function remember(array $candidates, ?string $browser): void
    {
        try {
            Cache::store('file')->put(
                $this->cacheKey($candidates),
                $browser ?? '',
                self::CACHE_TTL,
            );
        } catch (Throwable) {
            // Sin caché disponible la comprobación se repite en cada petición.
        }
    }

    /**
     * La clave depende de los candidatos: si se añade o quita un navegador,
     * la memoria caché deja de servir la respuesta anterior.
     *
     * @param  list<string>  $candidates
     */
    private function cacheKey(array $candidates): string
    {
        return 'pdf.chrome.'.md5(implode('|', $candidates));
    }

    /**
     * @return list<string>
     */
    private function globBrowsers(string $directory): array
    {
        $browsers = [];

        foreach (self::PATTERNS as $pattern) {
            $matches = glob($directory.'/'.$pattern);

            foreach ($matches === false ? [] : $matches as $match) {
                if (is_file($match)) {
                    $browsers[] = $match;
                }
            }
        }

        return $browsers;
    }
}
