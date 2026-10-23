<?php

declare(strict_types=1);

namespace App\Showcase;

/**
 * Una forma concreta de ejecutar una demostración.
 *
 * Por ejemplo, la demostración de motores tiene una variante por motor:
 * `dompdf`, `mpdf`, `browsershot` y `fake`.
 */
final readonly class ShowcaseVariant
{
    public function __construct(
        public string $key,
        public string $label,
        public string $description = '',
    ) {}

    public static function make(string $key, string $label, string $description = ''): self
    {
        return new self($key, $label, $description);
    }
}
