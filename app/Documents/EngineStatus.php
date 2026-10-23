<?php

declare(strict_types=1);

namespace App\Documents;

/**
 * Estado de un motor de PDF después de intentar renderizar un documento.
 */
final readonly class EngineStatus
{
    public const OK = 'ok';

    public const MISSING = 'missing';

    public const ERROR = 'error';

    /**
     * @param  int  $size  Tamaño del PDF generado en bytes.
     * @param  float  $durationMs  Tiempo de render en milisegundos.
     * @param  int  $warningCount  Número de avisos del motor.
     * @param  string  $message  Detalle del error, si lo hubo.
     */
    public function __construct(
        public string $driver,
        public string $state,
        public int $size = 0,
        public float $durationMs = 0.0,
        public int $warningCount = 0,
        public string $message = '',
    ) {}

    /**
     * Etiqueta legible del estado.
     */
    public function label(): string
    {
        return match ($this->state) {
            self::OK => 'OK',
            self::MISSING => 'NO INSTALADO',
            default => 'ERROR',
        };
    }

    public function isOk(): bool
    {
        return $this->state === self::OK;
    }
}
