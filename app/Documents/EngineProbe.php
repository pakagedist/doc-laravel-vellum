<?php

declare(strict_types=1);

namespace App\Documents;

use Throwable;
use Vellum\Exceptions\DriverNotSupportedException;
use Vellum\Facades\Pdf;

/**
 * Comprueba si un motor de PDF es capaz de renderizar un documento.
 *
 * Se usa en el panel de demostración y en el comando `pdf:check` para saber
 * qué motores están disponibles en la instalación actual.
 */
final class EngineProbe
{
    public function __construct(
        private readonly Engines $engines,
    ) {}

    /**
     * Renderiza un documento mínimo con el motor indicado.
     */
    public function probe(string $driver): EngineStatus
    {
        if (! $this->engines->isAvailable($driver)) {
            return new EngineStatus(
                driver: $driver,
                state: EngineStatus::MISSING,
                message: $this->engines->reason($driver),
            );
        }

        try {
            $result = Pdf::html('<p>Comprobación de PDF</p>')->driver($driver)->render();

            return new EngineStatus(
                driver: $driver,
                state: EngineStatus::OK,
                size: $result->size(),
                durationMs: $result->durationMs,
                warningCount: count($result->warnings),
            );
        } catch (DriverNotSupportedException) {
            return new EngineStatus(driver: $driver, state: EngineStatus::MISSING);
        } catch (Throwable $exception) {
            return new EngineStatus(
                driver: $driver,
                state: EngineStatus::ERROR,
                message: $exception->getMessage(),
            );
        }
    }

    /**
     * Comprueba todos los motores configurados en `config/pdf.php`.
     *
     * @return list<EngineStatus>
     */
    public function probeAll(): array
    {
        $drivers = array_keys((array) config('pdf.drivers', []));

        return array_map(fn (string $driver): EngineStatus => $this->probe($driver), $drivers);
    }
}
