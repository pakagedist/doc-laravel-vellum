<?php

declare(strict_types=1);

namespace App\Showcase;

/**
 * Un grupo de demostraciones: las fuentes, los motores, la maquetación…
 */
final readonly class ShowcaseCategory
{
    /**
     * @param  list<ShowcaseDemo>  $demos
     * @param  array{view: string, data: array<string, mixed>}|null  $aside
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $summary,
        public array $demos,
        public ?array $aside = null,
    ) {}

    public function demo(string $slug): ?ShowcaseDemo
    {
        foreach ($this->demos as $demo) {
            if ($demo->slug === $slug) {
                return $demo;
            }
        }

        return null;
    }
}
