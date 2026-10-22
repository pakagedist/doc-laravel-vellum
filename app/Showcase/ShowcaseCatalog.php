<?php

declare(strict_types=1);

namespace App\Showcase;

use App\Showcase\Categories\DocumentCategory;
use App\Showcase\Categories\DriverCategory;
use App\Showcase\Categories\FontCategory;
use App\Showcase\Categories\InternalsCategory;
use App\Showcase\Categories\LayoutCategory;
use App\Showcase\Categories\MetadataCategory;
use App\Showcase\Categories\OutputCategory;
use App\Showcase\Categories\SourceCategory;

/**
 * El catálogo completo del panel: qué se puede ver y dónde.
 */
final class ShowcaseCatalog
{
    /**
     * @var list<ShowcaseCategoryBuilder>
     */
    private array $builders;

    /**
     * @var list<ShowcaseCategory>|null
     */
    private ?array $cache = null;

    public function __construct(
        SourceCategory $sources,
        DriverCategory $drivers,
        LayoutCategory $layout,
        FontCategory $fonts,
        MetadataCategory $metadata,
        DocumentCategory $documents,
        OutputCategory $output,
        InternalsCategory $internals,
    ) {
        $this->builders = [$sources, $drivers, $layout, $fonts, $metadata, $documents, $output, $internals];
    }

    /**
     * Todas las categorías del panel.
     *
     * @return list<ShowcaseCategory>
     */
    public function categories(): array
    {
        return $this->cache ??= array_map(
            fn (ShowcaseCategoryBuilder $builder): ShowcaseCategory => $builder->build(),
            $this->builders,
        );
    }

    /**
     * Una categoría por su slug.
     */
    public function category(string $slug): ?ShowcaseCategory
    {
        foreach ($this->categories() as $category) {
            if ($category->slug === $slug) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Una demostración por su categoría y su slug.
     */
    public function demo(string $categorySlug, string $demoSlug): ?ShowcaseDemo
    {
        return $this->category($categorySlug)?->demo($demoSlug);
    }

    /**
     * Número total de demostraciones del panel.
     */
    public function totalDemos(): int
    {
        $total = 0;

        foreach ($this->categories() as $category) {
            $total += count($category->demos);
        }

        return $total;
    }
}
