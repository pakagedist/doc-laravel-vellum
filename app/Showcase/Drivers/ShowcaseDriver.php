<?php

declare(strict_types=1);

namespace App\Showcase\Drivers;

use Vellum\Config\ResolvedConfig;
use Vellum\Contracts\Capability;
use Vellum\Contracts\Driver;
use Vellum\Contracts\Source;
use Vellum\Support\Pdf\MiniPdf;

/**
 * Motor de demostración registrado por la propia aplicación.
 *
 * Implementa el contrato `Vellum\Contracts\Driver`, que es todo lo que hace
 * falta para enchufar un motor que no venga de serie.
 */
final class ShowcaseDriver implements Driver
{
    /**
     * Nombre con el que se usa en `->driver('showcase')`.
     */
    public function name(): string
    {
        return 'showcase';
    }

    /**
     * Convierte la fuente en bytes de PDF.
     */
    public function render(Source $source, ResolvedConfig $config): string
    {
        return MiniPdf::create(sprintf(
            'Motor propio de la aplicación. Fuente: %s (%s)',
            $source->describe(),
            $source->type(),
        ));
    }

    /**
     * Capacidades que anuncia el motor.
     *
     * @return list<string>
     */
    public function capabilities(): array
    {
        return Capability::all();
    }

    /**
     * ¿El motor admite esa capacidad?
     */
    public function supports(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }
}
