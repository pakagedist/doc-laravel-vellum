<?php

declare(strict_types=1);

namespace App\Showcase\Fonts;

/**
 * Una familia tipográfica que la instalación puede usar en un PDF.
 *
 * El origen no es un detalle: dompdf y mPDF solo ven las fuentes de su propio
 * catálogo, mientras que Chromium ve las que tiene instaladas el sistema.
 */
final readonly class FontFamily
{
    public const SANS = 'sans';

    public const SERIF = 'serif';

    public const MONO = 'mono';

    public const DISPLAY = 'display';

    public const SYSTEM = 'sistema';

    public const DOMPDF = 'dompdf';

    public const MPDF = 'mpdf';

    public const CORE = 'core';

    public const PROJECT = 'proyecto';

    public const LATIN = 'Latino';

    public const MULTI = 'Multiscript';

    /** Etiquetas de los motores tal y como se muestran en el panel. */
    public const DRIVER_LABELS = [
        'dompdf' => 'dompdf',
        'mpdf' => 'mPDF',
        'browsershot' => 'Chromium',
    ];

    /**
     * @param  list<string>  $drivers  Motores que pueden usar esta familia.
     * @param  string|null  $file  Archivo de la fuente, si se conoce.
     * @param  string  $script  Cobertura principal (Latino, CJK, Árabe…).
     */
    public function __construct(
        public string $key,
        public string $family,
        public string $kind,
        public string $origin,
        public array $drivers,
        public ?string $file = null,
        public string $note = '',
        public string $script = self::LATIN,
    ) {}

    /**
     * @param  array{key: string, family: string, kind: string, origin: string, drivers: list<string>, file: string|null, note: string, script: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            family: $data['family'],
            kind: $data['kind'],
            origin: $data['origin'],
            drivers: $data['drivers'],
            file: $data['file'],
            note: $data['note'],
            script: $data['script'],
        );
    }

    /**
     * @return array{key: string, family: string, kind: string, origin: string, drivers: list<string>, file: string|null, note: string, script: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'family' => $this->family,
            'kind' => $this->kind,
            'origin' => $this->origin,
            'drivers' => $this->drivers,
            'file' => $this->file,
            'note' => $this->note,
            'script' => $this->script,
        ];
    }

    public function supports(string $driver): bool
    {
        return in_array($driver, $this->drivers, true);
    }

    /**
     * El motor que mejor la reproduce: Chromium usa la fuente real y los otros
     * dos solo pueden con las familias de su catálogo. Las fuentes del núcleo
     * PDF se piden a dompdf, porque mPDF las sustituye por DejaVu.
     */
    public function preferredDriver(): ?string
    {
        $order = $this->origin === self::CORE
            ? ['dompdf', 'mpdf', 'browsershot']
            : ['browsershot', 'mpdf', 'dompdf'];

        foreach ($order as $driver) {
            if ($this->supports($driver)) {
                return $driver;
            }
        }

        return null;
    }

    /**
     * Tamaño del archivo de la fuente, en bytes.
     */
    public function size(): ?int
    {
        return $this->file !== null && is_file($this->file) ? (int) filesize($this->file) : null;
    }

    public function kilobytes(): ?string
    {
        $size = $this->size();

        return $size === null ? null : number_format($size / 1024).' KB';
    }

    public function fileName(): ?string
    {
        return $this->file === null ? null : basename($this->file);
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            self::SANS => 'Sans serif',
            self::SERIF => 'Serif',
            self::MONO => 'Monoespaciada',
            default => 'Decorativa',
        };
    }

    public function originLabel(): string
    {
        return match ($this->origin) {
            self::SYSTEM => 'Sistema',
            self::DOMPDF => 'dompdf',
            self::MPDF => 'mPDF',
            self::PROJECT => 'Proyecto',
            default => 'Núcleo PDF',
        };
    }

    /**
     * @return list<string>
     */
    public function driverLabels(): array
    {
        $labels = [];

        foreach ($this->drivers as $driver) {
            if (isset(self::DRIVER_LABELS[$driver])) {
                $labels[] = self::DRIVER_LABELS[$driver];
            }
        }

        return $labels;
    }
}
