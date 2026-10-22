<?php

declare(strict_types=1);

namespace App\Showcase;

/**
 * Resultado de ejecutar una demostración.
 *
 * Un resultado puede ser un PDF generado, un dato que se muestra en pantalla
 * (una tabla de capacidades, por ejemplo) o un aviso de que la demo no se
 * puede ejecutar en esta instalación.
 */
final readonly class ShowcaseOutcome
{
    public const OK = 'ok';

    public const UNAVAILABLE = 'unavailable';

    public const ERROR = 'error';

    /**
     * @param  list<array{label: string, value: string}>  $metrics
     * @param  list<string>  $notes
     * @param  list<array{label: string, url: string, kind: string}>  $links
     */
    public function __construct(
        public string $state = self::OK,
        public string $message = '',
        public ?string $bytes = null,
        public array $metrics = [],
        public array $notes = [],
        public bool $preview = true,
        public array $links = [],
    ) {}

    /**
     * @param  list<array{label: string, value: string}>  $metrics
     * @param  list<string>  $notes
     * @param  list<array{label: string, url: string, kind: string}>  $links
     */
    public static function ok(?string $bytes = null, array $metrics = [], array $notes = [], array $links = []): self
    {
        return new self(self::OK, bytes: $bytes, metrics: $metrics, notes: $notes, links: $links);
    }

    /**
     * @param  list<array{label: string, value: string}>  $metrics
     */
    public static function unavailable(string $message, array $metrics = []): self
    {
        return new self(self::UNAVAILABLE, $message, metrics: $metrics, preview: false);
    }

    /**
     * @param  list<array{label: string, value: string}>  $metrics
     */
    public static function error(string $message, array $metrics = []): self
    {
        return new self(self::ERROR, $message, metrics: $metrics, preview: false);
    }

    public function isOk(): bool
    {
        return $this->state === self::OK;
    }

    public function hasPreview(): bool
    {
        return $this->preview && $this->bytes !== null;
    }
}
