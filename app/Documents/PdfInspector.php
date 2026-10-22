<?php

declare(strict_types=1);

namespace App\Documents;

/**
 * Lectura de métricas básicas de un PDF ya generado.
 *
 * Permite comprobar el resultado de un render sin depender de herramientas
 * externas (pdfinfo, qpdf, Ghostscript…).
 */
final class PdfInspector
{
    /**
     * Número de páginas del documento.
     */
    public static function pageCount(string $bytes): int
    {
        if (preg_match('#/Type\s*/Pages\b[^>]*?/Count\s+(\d+)#s', $bytes, $matches) === 1) {
            return (int) $matches[1];
        }

        return max(1, preg_match_all('#/Type\s*/Page(?![a-zA-Z])#', $bytes));
    }

    /**
     * Tamaño de la página en puntos, tal y como lo declara el MediaBox.
     */
    public static function mediaBox(string $bytes): string
    {
        if (preg_match('#/MediaBox\s*\[([^\]]*)\]#', $bytes, $matches) !== 1) {
            return '';
        }

        return trim($matches[1]);
    }

    /**
     * Título declarado en los metadatos del documento.
     *
     * Los motores pueden escribirlo en texto plano o en UTF-16 con BOM, así
     * que se normaliza a UTF-8 antes de devolverlo.
     */
    public static function title(string $bytes): string
    {
        if (preg_match('#/Title\s*\((.*?)\)#s', $bytes, $matches) !== 1) {
            return '';
        }

        $title = $matches[1];

        if (str_starts_with($title, "\xFE\xFF")) {
            return trim((string) mb_convert_encoding(substr($title, 2), 'UTF-8', 'UTF-16BE'));
        }

        if (str_starts_with($title, "\xFF\xFE")) {
            return trim((string) mb_convert_encoding(substr($title, 2), 'UTF-8', 'UTF-16LE'));
        }

        return str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $title);
    }
}
