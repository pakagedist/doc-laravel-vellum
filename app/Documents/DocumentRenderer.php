<?php

declare(strict_types=1);

namespace App\Documents;

use Symfony\Component\HttpFoundation\Response;
use Vellum\PdfManager;
use Vellum\Support\PdfResult;

/**
 * Punto de entrada de la aplicación para generar documentos PDF.
 *
 * Envuelve al gestor de Vellum para que los controladores no dependan del
 * motor concreto: el documento se describe con una vista Blade y unas
 * opciones, el motor se decide en la configuración.
 */
final class DocumentRenderer
{
    public function __construct(private readonly PdfManager $pdf) {}

    /**
     * Renderiza una vista Blade como documento PDF.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data = [], ?DocumentOptions $options = null): PdfResult
    {
        $options ??= new DocumentOptions;

        // The builder is immutable: every option returns a new instance.
        $builder = $this->pdf->view($view, $data);

        if ($options->driver !== null) {
            $builder = $builder->driver($options->driver);
        }

        if ($options->paper !== null) {
            $builder = $builder->paper($options->paper, $options->orientation);
        } elseif ($options->orientation !== null) {
            $builder = $builder->orientation($options->orientation);
        }

        if ($options->margins !== null) {
            $builder = $builder->margins(
                $options->margins['top'],
                $options->margins['right'],
                $options->margins['bottom'],
                $options->margins['left'],
            );
        }

        if ($options->font !== null) {
            $builder = $builder->font($options->font, $options->fontFile);
        }

        if ($options->metadata !== []) {
            $builder = $builder->metadata($options->metadata);
        }

        if ($options->filename !== null) {
            $builder = $builder->filename($options->filename);
        }

        return $builder->render();
    }

    /**
     * Renderiza una vista Blade y devuelve el PDF como descarga.
     *
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data = [], ?DocumentOptions $options = null): Response
    {
        return $this->render($view, $data, $options)->download();
    }

    /**
     * Renderiza una vista Blade y devuelve el PDF en línea.
     *
     * @param  array<string, mixed>  $data
     */
    public function inline(string $view, array $data = [], ?DocumentOptions $options = null): Response
    {
        return $this->render($view, $data, $options)->stream();
    }
}
