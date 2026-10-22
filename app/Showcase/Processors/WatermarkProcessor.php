<?php

declare(strict_types=1);

namespace App\Showcase\Processors;

use Vellum\Contracts\Processor;
use Vellum\Pipeline\PdfContext;
use Vellum\Support\DocumentMetadata;
use Vellum\Support\Pdf\PdfEditor;
use Vellum\Support\Pdf\XmpPacket;

/**
 * Post-procesador propio de la aplicación.
 *
 * Los post-procesadores se ejecutan sobre el PDF ya generado y pueden
 * reescribir sus metadatos. Este añade una marca de agua a las propiedades del
 * documento y un identificador único en el paquete XMP.
 *
 * La marca de agua *visible* en el papel se hace con CSS en la plantilla; aquí
 * se ve la parte que solo se puede tocar en el PDF.
 */
final readonly class WatermarkProcessor implements Processor
{
    public function __construct(
        private string $text = 'CONFIDENCIAL',
        private string $author = 'Panel de demostración',
    ) {}

    /**
     * Nombre del post-procesador, tal y como aparece en los avisos.
     */
    public function name(): string
    {
        return 'watermark';
    }

    /**
     * Añade la marca de agua a los metadatos y al paquete XMP del PDF.
     */
    public function process(PdfContext $context): PdfContext
    {
        $metadata = DocumentMetadata::fromArray([
            'title' => 'Documento marcado',
            'author' => $this->author,
            'subject' => $this->text,
            'custom' => [
                'VellumWatermark' => $this->text,
                'VellumDocumentId' => substr(hash('sha256', $context->bytes), 0, 32),
            ],
        ]);

        $bytes = PdfEditor::setMetadata($context->bytes, $metadata);

        $packet = XmpPacket::build($metadata, [
            'vellum:Watermark' => $this->text,
        ], [
            'vellum' => 'https://laravel-vellum.test/ns/1.0',
        ]);

        return $context
            ->withBytes(PdfEditor::setXmp($bytes, $packet))
            ->withAttribute('watermark', $this->text);
    }
}
