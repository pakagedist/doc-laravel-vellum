<?php

declare(strict_types=1);

namespace App\Showcase\Support;

use RuntimeException;

/**
 * Certificado autofirmado para la demostración de firma digital.
 *
 * La librería firma en formato PKCS#12, así que hace falta un archivo `.p12`
 * con la clave privada y el certificado. En lugar de obligar a instalar uno,
 * el panel genera uno de prueba la primera vez y lo reutiliza.
 */
final class DemoCertificate
{
    /**
     * Ruta del archivo PKCS#12, generándolo si todavía no existe.
     */
    public function path(): string
    {
        $path = $this->directory().'/certificado-demo.p12';

        if (! is_file($path)) {
            $this->generate($path);
        }

        return $path;
    }

    /**
     * Descripción del certificado, para mostrarla en el panel.
     *
     * @return array<string, string>
     */
    public function details(): array
    {
        $path = $this->path();

        $certs = [];
        openssl_pkcs12_read((string) file_get_contents($path), $certs, '');

        $certificate = openssl_x509_parse((string) ($certs['cert'] ?? ''));

        return [
            'Emisor' => (string) ($certificate['name'] ?? 'desconocido'),
            'Subject' => (string) ($certificate['subject']['CN'] ?? 'desconocido'),
            'Válido desde' => date('d/m/Y H:i', (int) ($certificate['validFrom_time_t'] ?? 0)),
            'Válido hasta' => date('d/m/Y H:i', (int) ($certificate['validTo_time_t'] ?? 0)),
            'Formato' => 'PKCS#12 (.p12) con clave privada',
            'Contraseña' => '(vacía)',
        ];
    }

    private function directory(): string
    {
        $directory = storage_path('app/pdf-showcase');

        if (! is_dir($directory) && ! mkdir($directory, 0o755, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('The directory [%s] could not be created.', $directory));
        }

        return $directory;
    }

    /**
     * Genera un par de claves RSA y lo exporta como PKCS#12.
     */
    private function generate(string $path): void
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            throw new RuntimeException('The demo certificate could not be created: '.openssl_error_string());
        }

        $csr = openssl_csr_new([
            'commonName' => 'Vellum Demo',
            'organizationName' => 'Vellum Fábrica de PDF',
            'countryName' => 'ES',
        ], $key, ['digest_alg' => 'sha256']);

        if ($csr === false) {
            throw new RuntimeException('The demo certificate request could not be created.');
        }

        $certificate = openssl_csr_sign($csr, null, $key, 365, ['digest_alg' => 'sha256']);

        if ($certificate === false) {
            throw new RuntimeException('The demo certificate could not be signed.');
        }

        $privateKey = '';
        openssl_pkey_export($key, $privateKey);

        $certificatePem = '';
        openssl_x509_export($certificate, $certificatePem);

        $pkcs12 = '';

        $exported = openssl_pkcs12_export($certificatePem, $pkcs12, $privateKey, '', [
            'friendly_name' => 'Vellum Demo',
        ]);

        if ($exported === false) {
            throw new RuntimeException('The demo certificate could not be exported: '.openssl_error_string());
        }

        file_put_contents($path, $pkcs12);
    }
}
