<?php

declare(strict_types=1);

namespace App\Showcase;

use Closure;

/**
 * Una demostración concreta de la API de Vellum.
 *
 * Cada demostración sabe cómo construir su documento (`$handler`) y describe
 * el código que lo hace, para poder mostrarlo en la interfaz.
 */
final readonly class ShowcaseDemo
{
    /**
     * @param  list<ShowcaseVariant>  $variants
     * @param  Closure(ShowcaseVariant): ShowcaseOutcome  $handler
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $summary,
        public string $code,
        public array $variants,
        public Closure $handler,
    ) {}

    /**
     * Busca una variante por clave y, si no existe, devuelve la primera.
     */
    public function variant(string $key): ShowcaseVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->key === $key) {
                return $variant;
            }
        }

        return $this->defaultVariant();
    }

    public function defaultVariant(): ShowcaseVariant
    {
        return $this->variants[0];
    }
}
