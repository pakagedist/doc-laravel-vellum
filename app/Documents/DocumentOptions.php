<?php

declare(strict_types=1);

namespace App\Documents;

/**
 * Opciones de un documento PDF.
 *
 * Todo es opcional: lo que no se define toma el valor de `config/pdf.php`
 * (motor, papel, orientación, márgenes y metadatos por defecto).
 */
final readonly class DocumentOptions
{
    /**
     * @param  array<string, string>  $metadata
     * @param  array{top: float, right: float, bottom: float, left: float}|null  $margins
     */
    public function __construct(
        public ?string $driver = null,
        public ?string $paper = null,
        public ?string $orientation = null,
        public ?string $filename = null,
        public array $metadata = [],
        public ?array $margins = null,
        public ?string $font = null,
        public ?string $fontFile = null,
    ) {}
}
