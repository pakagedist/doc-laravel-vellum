<?php

declare(strict_types=1);

namespace App\Showcase\Fonts;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Mpdf\Config\FontVariables;
use Throwable;

/**
 * Las fuentes que esta instalación puede usar de verdad.
 *
 * El catálogo se construye a partir de cuatro orígenes —fontconfig, dompdf,
 * mPDF y la carpeta `resources/fonts` del proyecto—, se fusiona por familia y
 * se guarda en caché: descubrir las fuentes requiere leer el sistema y los
 * catálogos de los motores, y eso no cambia entre peticiones.
 */
final class FontCatalog
{
    /**
     * Sube este número cuando cambie el formato de los datos cacheados.
     */
    private const CACHE_VERSION = 2;

    private const CACHE_TTL = 86400;

    /**
     * Orden de preferencia dentro del catálogo (las tres DejaVu primero).
     */
    private const PRIORITY = [
        'dejavu-sans' => 0,
        'dejavu-serif' => 1,
        'dejavu-sans-mono' => 2,
    ];

    /**
     * Familias de mPDF: clave de `fontdata` → nombre, tipo, cobertura y nota.
     */
    private const MPDF = [
        'dejavusans' => ['DejaVu Sans', FontFamily::SANS, FontFamily::LATIN, 'La opción segura: la pueden usar los tres motores.'],
        'dejavusanscondensed' => ['DejaVu Sans Condensed', FontFamily::SANS, FontFamily::LATIN, 'Solo está en el catálogo de mPDF.'],
        'dejavuserif' => ['DejaVu Serif', FontFamily::SERIF, FontFamily::LATIN, 'Serif con amplia cobertura de acentos.'],
        'dejavuserifcondensed' => ['DejaVu Serif Condensed', FontFamily::SERIF, FontFamily::LATIN, 'Solo está en el catálogo de mPDF.'],
        'dejavusansmono' => ['DejaVu Sans Mono', FontFamily::MONO, FontFamily::LATIN, 'Monoespaciada para tablas y código.'],
        'freesans' => ['FreeSans', FontFamily::SANS, FontFamily::LATIN, 'Sustituta libre de Helvetica.'],
        'freeserif' => ['FreeSerif', FontFamily::SERIF, FontFamily::LATIN, 'Sustituta libre de Times.'],
        'freemono' => ['FreeMono', FontFamily::MONO, FontFamily::LATIN, 'Sustituta libre de Courier.'],
        'ocrb' => ['OCR-B', FontFamily::MONO, FontFamily::LATIN, 'Reconocimiento óptico: cifras y códigos.'],
        'quivira' => ['Quivira', FontFamily::SANS, FontFamily::MULTI, 'Cobertura Unicode enorme, pero sin negrita real.'],
        'mph2bdamase' => ['MPH 2B Damase', FontFamily::SANS, FontFamily::MULTI, 'Armenio, georgiano, griego, cirílico y más.'],

        'sun-exta' => ['Sun-ExtA', FontFamily::SANS, 'CJK', 'CJK básico; incluye latino y kana.'],
        'sun-extb' => ['Sun-ExtB', FontFamily::SANS, 'CJK', 'CJK de planos extendidos (poco habituales).'],
        'unbatang' => ['UnBatang', FontFamily::SERIF, 'Coreano', 'Serif coreana con Hangul completo.'],

        'xbriyaz' => ['XB Riyaz', FontFamily::SANS, 'Árabe', 'Árabe con shaping OTL y kashida.'],
        'lateef' => ['Lateef', FontFamily::SERIF, 'Árabe', 'Árabe para urdu, pastún y sindhi.'],
        'kfgqpcuthmantahanaskh' => ['KFGQPC Uthman Taha Naskh', FontFamily::SERIF, 'Árabe', 'Naskh coránico.'],
        'taameydavidclm' => ['Taamey David CLM', FontFamily::SERIF, 'Hebreo', 'Hebreo con niqud y cantilación.'],

        'garuda' => ['Garuda', FontFamily::SANS, 'Tailandés', 'Tailandesa sin serif.'],
        'khmeros' => ['KhmerOS', FontFamily::SANS, 'Jemer', 'Jemer (camboyano).'],
        'tharlon' => ['Tharlon', FontFamily::SANS, 'Birmano', 'Birmano.'],
        'padaukbook' => ['Padauk Book', FontFamily::SANS, 'Birmano', 'Birmano con cobertura amplia.'],
        'zawgyi-one' => ['Zawgyi-One', FontFamily::SANS, 'Birmano', 'Codificación Zawgyi (heredada).'],
        'ayar' => ['Ayar', FontFamily::SANS, 'Birmano', 'Birmano.'],
        'lohitkannada' => ['Lohit Kannada', FontFamily::SANS, 'Kannada', 'Kannada.'],
        'pothana2000' => ['Pothana 2000', FontFamily::SANS, 'Telugu', 'Telugu.'],
        'estrangeloedessa' => ['Estrangelo Edessa', FontFamily::SERIF, 'Siriaco', 'Siriaco.'],
        'abyssinicasil' => ['Abyssinica SIL', FontFamily::SERIF, 'Etíope', 'Etíope (Ge\'ez).'],
        'jomolhari' => ['Jomolhari', FontFamily::SERIF, 'Tibetano', 'Tibetano (dzongkha).'],
        'dhyana' => ['Dhyana', FontFamily::SANS, 'Tai', 'Lao y tai.'],
        'lannaalif' => ['Lanna Alif', FontFamily::SERIF, 'Tai', 'Tai Tham (lanna).'],
        'taiheritagepro' => ['Tai Heritage Pro', FontFamily::SERIF, 'Tai', 'Tai Dam.'],
        'daibannasilbook' => ['Dai Banna SIL Book', FontFamily::SERIF, 'Tai', 'Tai Lue.'],
        'eeyekunicode' => ['Eeyek Unicode', FontFamily::SANS, 'Meitei', 'Meetei Mayek (manipurí).'],
        'sundaneseunicode' => ['Sundanese Unicode', FontFamily::SANS, 'Sondanés', 'Sondanés.'],
        'aboriginalsans' => ['Aboriginal Sans', FontFamily::SANS, 'Aborigen', 'Silabarios aborígenes canadienses.'],
        'kaputaunicode' => ['Kaputa Unicode', FontFamily::SANS, 'Otros', 'Cobertura adicional de mPDF.'],
        'aegean' => ['Aegean', FontFamily::SANS, 'Antiguo', 'Alfabetos egeos antiguos.'],
        'aegyptus' => ['Aegyptus', FontFamily::SANS, 'Antiguo', 'Jeroglíficos egipcios.'],
        'akkadian' => ['Akkadian', FontFamily::SANS, 'Antiguo', 'Cuneiforme acadio.'],
    ];

    /**
     * Familias que trae dompdf con su archivo.
     */
    private const DOMPDF = [
        ['dejavu-sans', 'DejaVu Sans', FontFamily::SANS, 'vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'],
        ['dejavu-serif', 'DejaVu Serif', FontFamily::SERIF, 'vendor/dompdf/dompdf/lib/fonts/DejaVuSerif.ttf'],
        ['dejavu-sans-mono', 'DejaVu Sans Mono', FontFamily::MONO, 'vendor/dompdf/dompdf/lib/fonts/DejaVuSansMono.ttf'],
    ];

    /**
     * Fuentes del núcleo PDF: no se incrustan.
     */
    private const CORE = [
        ['helvetica', 'Helvetica', FontFamily::SANS, 'Núcleo PDF: no se incrusta. mPDF la sustituye por DejaVu Sans Condensed.'],
        ['times', 'Times', FontFamily::SERIF, 'Núcleo PDF: no se incrusta. mPDF la sustituye por DejaVu Serif Condensed.'],
        ['courier', 'Courier', FontFamily::MONO, 'Núcleo PDF: no se incrusta. mPDF la sustituye por DejaVu Sans Mono.'],
    ];

    /**
     * Tipos que el nombre de la fuente no deja adivinar.
     */
    private const KINDS = [
        'c059' => FontFamily::SERIF,
        'p052' => FontFamily::SERIF,
        'urwbookman' => FontFamily::SERIF,
        'urwgothic' => FontFamily::SANS,
        'nimbusroman' => FontFamily::SERIF,
        'z003' => FontFamily::DISPLAY,
        'unbatang' => FontFamily::SERIF,
        'jomolhari' => FontFamily::SERIF,
        'sun-exta' => FontFamily::SANS,
        'sun-extb' => FontFamily::SANS,
    ];

    /**
     * @var list<FontFamily>|null
     */
    private ?array $families = null;

    /**
     * Todas las familias disponibles, en el orden del catálogo.
     *
     * @return list<FontFamily>
     */
    public function all(): array
    {
        if ($this->families !== null) {
            return $this->families;
        }

        return $this->families = array_map(
            FontFamily::fromArray(...),
            $this->load(),
        );
    }

    public function find(string $key): ?FontFamily
    {
        foreach ($this->all() as $font) {
            if ($font->key === $key) {
                return $font;
            }
        }

        return null;
    }

    /**
     * Las familias que un motor concreto puede usar.
     *
     * @return list<FontFamily>
     */
    public function forDriver(string $driver): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (FontFamily $font): bool => $font->supports($driver),
        ));
    }

    /**
     * Las familias que se pueden mostrar con texto latino.
     *
     * @return list<FontFamily>
     */
    public function latin(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (FontFamily $font): bool => in_array($font->script, [FontFamily::LATIN, FontFamily::MULTI], true),
        ));
    }

    /**
     * Cuántas familias hay de cada origen y cobertura.
     *
     * @return array<string, int>
     */
    public function totals(): array
    {
        $totals = ['total' => 0, 'latino' => 0, 'otros' => 0];

        foreach ($this->all() as $font) {
            $totals['total']++;
            $totals[$font->origin] = ($totals[$font->origin] ?? 0) + 1;

            if (in_array($font->script, [FontFamily::LATIN, FontFamily::MULTI], true)) {
                $totals['latino']++;
            } else {
                $totals['otros']++;
            }
        }

        return $totals;
    }

    /**
     * Archivos de fuente que traen instalados los motores.
     *
     * @return array{mpdf: int, dompdf: int}
     */
    public function bundled(): array
    {
        return [
            'mpdf' => count(glob(base_path('vendor/mpdf/mpdf/ttfonts/*.ttf')) ?: []),
            'dompdf' => count(glob(base_path('vendor/dompdf/dompdf/lib/fonts/*.ttf')) ?: []),
        ];
    }

    /**
     * Vacía la caché del catálogo (después de instalar fuentes nuevas).
     */
    public function flush(): void
    {
        $this->families = null;

        Cache::store('file')->forget($this->cacheKey());
    }

    /**
     * @return list<array{key: string, family: string, kind: string, origin: string, drivers: list<string>, file: string|null, note: string, script: string}>
     */
    private function load(): array
    {
        try {
            return Cache::store('file')->remember(
                $this->cacheKey(),
                self::CACHE_TTL,
                fn (): array => $this->discover(),
            );
        } catch (Throwable) {
            // Sin caché disponible el panel sigue funcionando: solo cuesta más.
            return $this->discover();
        }
    }

    /**
     * La clave incluye la fecha del archivo para invalidarla al cambiar el código.
     */
    private function cacheKey(): string
    {
        return 'showcase.fonts.'.self::CACHE_VERSION.'.'.filemtime(__FILE__);
    }

    /**
     * @return list<array{key: string, family: string, kind: string, origin: string, drivers: list<string>, file: string|null, note: string, script: string}>
     */
    private function discover(): array
    {
        $entries = [];

        $this->addSystem($entries);
        $this->addDompdf($entries);
        $this->addMpdf($entries);
        $this->addProject($entries);

        usort($entries, fn (array $a, array $b): int => [
            $this->scriptWeight($a['script']),
            self::PRIORITY[$a['key']] ?? 100,
            $a['script'],
            $a['family'],
        ] <=> [
            $this->scriptWeight($b['script']),
            self::PRIORITY[$b['key']] ?? 100,
            $b['script'],
            $b['family'],
        ]);

        return $entries;
    }

    /**
     * Las familias instaladas en el sistema, en una sola llamada a fontconfig.
     *
     * @param  array<string, array<string, mixed>>  $entries
     */
    private function addSystem(array &$entries): void
    {
        $output = $this->shell("fc-list --format='%{family}|%{file}|%{style}\\n'");

        if ($output === null) {
            return;
        }

        $rows = [];

        foreach (explode("\n", $output) as $line) {
            $parts = explode('|', $line);

            if (count($parts) < 3) {
                continue;
            }

            [$families, $file, $style] = $parts;

            foreach (array_unique(array_map('trim', explode(',', $families))) as $family) {
                if ($family === '' || $this->isIgnored($family)) {
                    continue;
                }

                $rows[] = ['family' => $family, 'file' => $file, 'style' => $style];
            }
        }

        // El archivo «Regular» es el que mejor representa el tamaño de la familia.
        usort($rows, fn (array $a, array $b): int => $this->styleWeight($a['style']) <=> $this->styleWeight($b['style']));

        foreach ($rows as $row) {
            $this->merge($entries, [
                'key' => Str::slug($row['family']),
                'family' => $row['family'],
                'kind' => $this->kindFor($row['family']),
                'origin' => FontFamily::SYSTEM,
                'drivers' => ['browsershot'],
                'file' => is_file($row['file']) ? $row['file'] : null,
                'note' => 'Instalada en el sistema: solo la ve Chromium.',
                'script' => $this->systemScript($row['family']),
            ]);
        }
    }

    /**
     * Las familias y las fuentes del núcleo que trae dompdf.
     *
     * @param  array<string, array<string, mixed>>  $entries
     */
    private function addDompdf(array &$entries): void
    {
        foreach (self::DOMPDF as [$key, $family, $kind, $file]) {
            $path = base_path($file);

            if (! is_file($path)) {
                continue;
            }

            $this->merge($entries, [
                'key' => $key,
                'family' => $family,
                'kind' => $kind,
                'origin' => FontFamily::DOMPDF,
                'drivers' => ['dompdf', 'mpdf'],
                'file' => $path,
                'note' => 'dompdf y mPDF la traen en su propio catálogo.',
                'script' => FontFamily::LATIN,
            ]);
        }

        foreach (self::CORE as [$key, $family, $kind, $note]) {
            $this->merge($entries, [
                'key' => $key,
                'family' => $family,
                'kind' => $kind,
                'origin' => FontFamily::CORE,
                'drivers' => ['dompdf', 'mpdf'],
                'file' => null,
                'note' => $note,
                'script' => FontFamily::LATIN,
            ]);
        }
    }

    /**
     * Las familias registradas en mPDF (`fontdata`), no solo los TTF del disco.
     *
     * @param  array<string, array<string, mixed>>  $entries
     */
    private function addMpdf(array &$entries): void
    {
        try {
            $data = (new FontVariables)->getDefaults()['fontdata'];
        } catch (Throwable) {
            return;
        }

        foreach ($data as $name => $font) {
            $definition = self::MPDF[$name] ?? null;

            if ($definition === null) {
                continue;
            }

            [$family, $kind, $script, $note] = $definition;

            $file = base_path('vendor/mpdf/mpdf/ttfonts/'.($font['R'] ?? ''));

            if (! is_file($file)) {
                continue;
            }

            $this->merge($entries, [
                'key' => Str::slug($family),
                'family' => $family,
                'kind' => $kind,
                'origin' => FontFamily::MPDF,
                'drivers' => ['mpdf'],
                'file' => $file,
                'note' => $note,
                'script' => $script,
            ]);
        }
    }

    /**
     * Los TTF que el proyecto deja en `resources/fonts`.
     *
     * @param  array<string, array<string, mixed>>  $entries
     */
    private function addProject(array &$entries): void
    {
        foreach (glob(resource_path('fonts').'/*.ttf') ?: [] as $file) {
            $family = $this->prettyName(pathinfo($file, PATHINFO_FILENAME));

            $this->merge($entries, [
                'key' => Str::slug($family),
                'family' => $family,
                'kind' => $this->kindFor($family),
                'origin' => FontFamily::PROJECT,
                'drivers' => ['dompdf'],
                'file' => $file,
                'note' => 'Archivo del proyecto: úsalo con font(\'Alias\', $ruta). Para Chromium, instálalo en el sistema.',
                'script' => FontFamily::LATIN,
            ]);
        }
    }

    /**
     * Fusiona una familia con las que ya se han descubierto.
     *
     * @param  array<string, array<string, mixed>>  $entries
     * @param  array<string, mixed>  $entry
     */
    private function merge(array &$entries, array $entry): void
    {
        $key = $entry['key'];

        if (! isset($entries[$key])) {
            $entries[$key] = $entry;

            return;
        }

        $entries[$key]['drivers'] = array_values(array_unique([...$entries[$key]['drivers'], ...$entry['drivers']]));
        $entries[$key]['file'] ??= $entry['file'];
        $entries[$key]['note'] = $entries[$key]['note'] !== '' ? $entries[$key]['note'] : $entry['note'];

        if ($entries[$key]['script'] === FontFamily::MULTI && $entry['script'] !== FontFamily::MULTI) {
            $entries[$key]['script'] = $entry['script'];
        }
    }

    private function kindFor(string $family): string
    {
        $name = $this->normalize($family);

        if (isset(self::KINDS[$name])) {
            return self::KINDS[$name];
        }

        return match (true) {
            str_contains($name, 'mono'),
            str_contains($name, 'courier'),
            str_contains($name, 'code'),
            str_contains($name, 'ocr') => FontFamily::MONO,
            str_contains($name, 'sans'),
            str_contains($name, 'gothic'),
            str_contains($name, 'grotesk') => FontFamily::SANS,
            str_contains($name, 'serif'),
            str_contains($name, 'roman'),
            str_contains($name, 'bookman') => FontFamily::SERIF,
            default => FontFamily::SANS,
        };
    }

    private function systemScript(string $family): string
    {
        $name = $this->normalize($family);

        return str_contains($name, 'cjk') || str_contains($name, 'fallback') ? 'CJK' : FontFamily::LATIN;
    }

    private function scriptWeight(string $script): int
    {
        return in_array($script, [FontFamily::LATIN, FontFamily::MULTI], true) ? 0 : 1;
    }

    private function styleWeight(string $style): int
    {
        $style = strtolower($style);

        return match (true) {
            str_contains($style, 'regular'),
            str_contains($style, 'book'),
            str_contains($style, 'roman'),
            str_contains($style, 'normal') => 0,
            str_contains($style, 'medium'),
            str_contains($style, 'oblique'),
            str_contains($style, 'italic') => 2,
            str_contains($style, 'bold') => 3,
            default => 1,
        };
    }

    private function isIgnored(string $family): bool
    {
        $name = $this->normalize($family);

        return str_starts_with($name, 'mathjax')
            || in_array($name, ['notocoloremoji', 'standardsymbolsps', 'd050000l'], true);
    }

    /**
     * «VellumSans-SemiBold» → «Vellum Sans».
     */
    private function prettyName(string $name): string
    {
        $name = preg_replace('/[-_ ]?(Thin|ExtraLight|Light|Regular|Book|Medium|SemiBold|DemiBold|Bold|ExtraBold|Black|Italic|Oblique)+$/i', '', $name) ?? $name;
        $name = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $name) ?? $name;

        return trim(str_replace(['-', '_'], ' ', $name));
    }

    private function normalize(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $value));
    }

    private function shell(string $command): ?string
    {
        if (! function_exists('shell_exec')) {
            return null;
        }

        $output = @shell_exec($command);

        return is_string($output) ? $output : null;
    }
}
