<?php

declare(strict_types=1);

namespace App\Showcase\Categories;

use App\Showcase\Processors\WatermarkProcessor;
use App\Showcase\ShowcaseCategory;
use App\Showcase\ShowcaseCategoryBuilder;
use App\Showcase\ShowcaseDemo;
use App\Showcase\ShowcaseOutcome;
use App\Showcase\ShowcaseVariant;
use App\Showcase\Support\DemoCertificate;
use App\Showcase\Support\SampleData;
use Vellum\Exceptions\PdfGenerationException;
use Vellum\PdfManager;

/**
 * El post-procesado: lo que se le hace al PDF después de renderizarlo.
 */
final class DocumentCategory extends ShowcaseCategoryBuilder
{
    public function __construct(
        private readonly PdfManager $pdf,
        private readonly DemoCertificate $certificate,
    ) {}

    public function build(): ShowcaseCategory
    {
        return new ShowcaseCategory(
            slug: 'documentos',
            title: 'Post-procesado del documento',
            summary: 'Una vez renderizado, el PDF se puede enriquecer: adjuntar archivos, unir varios '
                .'documentos, firmarlo criptográficamente o pasarle un post-procesador propio.',
            demos: [
                $this->embedDemo(),
                $this->mergeDemo(),
                $this->signDemo(),
                $this->processorDemo(),
            ],
        );
    }

    private function embedDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            $result = Pdf::view('documents.invoice', $data)
                ->embedFile(
                    path: base_path('resources/showcase/factur-x.xml'),
                    name: 'factur-x.xml',
                    relationship: 'Data',
                    mimeType: 'text/xml',
                )
                ->render();
            PHP;

        return $this->demo(
            slug: 'adjuntar',
            title: 'Archivos adjuntos',
            summary: 'Se puede incrustar cualquier archivo dentro del PDF. El nombre, la relación y el tipo '
                .'MIME se eligen al adjuntarlo.',
            code: $code,
            variants: [
                $this->variant('xml', 'XML adjunto', 'Relación «Data», la habitual.'),
                $this->variant('alternativo', 'Como alternativa legible', 'Relación «Alternative», la de Factur-X.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $xml = base_path('resources/showcase/factur-x.xml');

                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->embedFile(
                        path: $xml,
                        name: 'factur-x.xml',
                        relationship: $variant->key === 'alternativo' ? 'Alternative' : 'Data',
                        mimeType: 'text/xml',
                    )
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'Adjunto', 'value' => 'factur-x.xml'],
                    ['label' => 'Relación', 'value' => $variant->key === 'alternativo' ? 'Alternative' : 'Data'],
                ]), [
                    'El adjunto se registra en el árbol de nombres del catálogo del PDF.',
                ]);
            },
        );
    }

    private function mergeDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            // Los PDFs se concatenan en el orden indicado, detrás del actual.
            $result = Pdf::view('documents.invoice', $data)
                ->merge([
                    storage_path('app/pdf-showcase/anexo-1.pdf'),
                    storage_path('app/pdf-showcase/anexo-2.pdf'),
                ])
                ->render();

            // Necesita el paquete setasign/fpdi.
            PHP;

        return $this->demo(
            slug: 'combinar',
            title: 'Unir documentos',
            summary: 'El documento renderizado se une con los PDF indicados, en ese orden. Requiere '
                .'`setasign/fpdi`, que ya está instalado en la aplicación.',
            code: $code,
            variants: [
                $this->variant('anexos', 'Factura + 2 anexos', 'Tres documentos en uno.'),
                $this->variant('uno', 'Factura + 1 anexo', 'Un solo PDF detrás.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $anexos = $this->anexos($variant->key === 'anexos' ? 2 : 1);

                $result = $this->pdf
                    ->view('documents.invoice', ['factura' => SampleData::invoice()])
                    ->merge($anexos)
                    ->render();

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'Anexos unidos', 'value' => (string) count($anexos)],
                ]), [
                    'Los anexos se generan al vuelo con el mismo gestor de Vellum.',
                ]);
            },
        );
    }

    private function signDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use Vellum\Facades\Pdf;

            $result = Pdf::view('documents.invoice', $data)
                ->sign(
                    certificate: storage_path('app/pdf-showcase/certificado-demo.p12'),
                    password: '',
                    options: [
                        'name' => 'Firma de la aplicación',
                        'reason' => 'Emisión de factura',
                        'location' => 'Madrid',
                        'contact_info' => 'facturacion@vellum.test',
                    ],
                )
                ->render();
            PHP;

        return $this->demo(
            slug: 'firmar',
            title: 'Firma digital',
            summary: 'Firma PKCS#7 desligada: el PDF lleva una firma verificable con el certificado '
                .'indicado y un widget de firma visible en la primera página.',
            code: $code,
            variants: [
                $this->variant('firmado', 'Documento firmado', 'Con los datos del firmante.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                try {
                    $result = $this->pdf
                        ->view('documents.invoice', ['factura' => SampleData::invoice()])
                        ->sign($this->certificate->path(), '', [
                            'name' => 'Firma de la aplicación',
                            'reason' => 'Emisión de factura',
                            'location' => 'Madrid',
                            'contact_info' => 'facturacion@vellum.test',
                        ])
                        ->render();
                } catch (PdfGenerationException $exception) {
                    return ShowcaseOutcome::unavailable($exception->getMessage());
                }

                $firmado = str_contains($result->bytes, '/SubFilter /adbe.pkcs7.detached') ? 'sí' : 'no';

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'Firma PKCS#7', 'value' => $firmado],
                    ['label' => 'Certificado', 'value' => 'autofirmado de demostración'],
                ]), $this->certificate->details());
            },
        );
    }

    private function processorDemo(): ShowcaseDemo
    {
        $code = <<<'PHP'
            use App\Showcase\Processors\WatermarkProcessor;
            use Vellum\Contracts\Processor;
            use Vellum\Facades\Pdf;

            // Un post-procesador propio implementa el contrato Processor:
            //   name(): string
            //   process(PdfContext $context): PdfContext

            $result = Pdf::view('documents.invoice', $data)
                ->process(new WatermarkProcessor('CONFIDENCIAL'))
                ->render();
            PHP;

        return $this->demo(
            slug: 'procesador',
            title: 'Post-procesador propio',
            summary: 'Implementando `Vellum\\Contracts\\Processor` se puede alterar el PDF ya generado. '
                .'Esta demo añade la marca de agua a las propiedades del documento y al paquete XMP.',
            code: $code,
            variants: [
                $this->variant('marcado', 'Documento marcado', 'Con el post-procesador aplicado.'),
                $this->variant('limpio', 'Documento sin marcar', 'Para comparar.'),
            ],
            handler: function (ShowcaseVariant $variant): ShowcaseOutcome {
                $builder = $this->pdf->view('documents.invoice', ['factura' => SampleData::invoice()]);

                if ($variant->key === 'marcado') {
                    $builder = $builder->process(new WatermarkProcessor('CONFIDENCIAL'));
                }

                $result = $builder->render();

                $marcado = str_contains($result->bytes, 'CONFIDENCIAL') ? 'sí' : 'no';

                return ShowcaseOutcome::ok($result->bytes, array_merge($this->metrics($result), [
                    ['label' => 'Marca de agua', 'value' => $marcado],
                ]), [
                    'La marca visible en el papel se hace con CSS; la marca en las propiedades, con el procesador.',
                ]);
            },
        );
    }

    /**
     * Genera los anexos que se unen al documento principal.
     *
     * @return list<string>
     */
    private function anexos(int $cuantos): array
    {
        $directorio = storage_path('app/pdf-showcase');

        if (! is_dir($directorio)) {
            mkdir($directorio, 0o755, true);
        }

        $rutas = [];

        for ($i = 1; $i <= $cuantos; $i++) {
            $ruta = $directorio.'/anexo-'.$i.'.pdf';

            $this->pdf
                ->html('<h1>Anexo '.$i.'</h1><p>Documento generado para la demostración de unión de PDFs.</p>')
                ->save($ruta);

            $rutas[] = $ruta;
        }

        return $rutas;
    }
}
