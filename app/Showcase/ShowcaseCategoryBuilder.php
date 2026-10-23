<?php

declare(strict_types=1);

namespace App\Showcase;

use App\Documents\PdfInspector;
use Closure;
use Vellum\Support\PdfResult;

/**
 * Base de los constructores de categorías del panel.
 *
 * Concentra las utilidades que comparten todas las categorías: crear
 * demostraciones, variantes y métricas a partir del resultado de un render.
 */
abstract class ShowcaseCategoryBuilder
{
    /**
     * Crea una demostración del panel.
     *
     * @param  list<ShowcaseVariant>  $variants
     * @param  Closure(ShowcaseVariant): ShowcaseOutcome  $handler
     */
    protected function demo(
        string $slug,
        string $title,
        string $summary,
        string $code,
        array $variants,
        Closure $handler,
    ): ShowcaseDemo {
        return new ShowcaseDemo(
            slug: $slug,
            title: $title,
            summary: $summary,
            code: $code,
            variants: $variants,
            handler: $handler,
        );
    }

    /**
     * Crea una variante de una demostración.
     */
    protected function variant(string $key, string $label, string $description = ''): ShowcaseVariant
    {
        return ShowcaseVariant::make($key, $label, $description);
    }

    /**
     * Métricas de un PDF recién generado.
     *
     * @return list<array{label: string, value: string}>
     */
    protected function metrics(PdfResult $result): array
    {
        return [
            ['label' => 'Motor', 'value' => $result->driver],
            ['label' => 'Fuente', 'value' => $result->sourceType],
            ['label' => 'Tamaño', 'value' => $this->kilobytes($result->size())],
            ['label' => 'Duración', 'value' => number_format($result->durationMs, 1).' ms'],
            ['label' => 'Páginas', 'value' => (string) PdfInspector::pageCount($result->bytes)],
            ['label' => 'MediaBox', 'value' => PdfInspector::mediaBox($result->bytes) ?: '—'],
            ['label' => 'SHA-256', 'value' => substr($result->sha256(), 0, 24).'…'],
            ['label' => 'Avisos', 'value' => (string) count($result->warnings)],
        ];
    }

    /**
     * Tamaño en kilobytes, con un decimal.
     */
    protected function kilobytes(int $bytes): string
    {
        return number_format($bytes / 1024, 1).' KB';
    }
}
