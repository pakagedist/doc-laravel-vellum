<?php

declare(strict_types=1);

namespace App\Showcase\Sources;

use Vellum\Config\ResolvedConfig;
use Vellum\Contracts\Source;

/**
 * Fuente de documento propia de la aplicación.
 *
 * Implementa el contrato `Vellum\Contracts\Source` para resolver el contenido
 * como quiera la aplicación (en este caso, renderizando una vista Blade) y
 * entregarlo a Vellum como si fuera cualquier otra fuente.
 */
final readonly class PlantillaSource implements Source
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private string $view,
        private array $data = [],
    ) {}

    /**
     * Tipo de fuente, tal y como aparece en las métricas del render.
     */
    public function type(): string
    {
        return 'plantilla';
    }

    /**
     * Descripción legible de la fuente.
     */
    public function describe(): string
    {
        return 'plantilla Blade propia ('.$this->view.')';
    }

    /**
     * Devuelve el HTML que se quiere convertir en PDF.
     */
    public function load(ResolvedConfig $config): string
    {
        return view($this->view, $this->data)->render();
    }
}
