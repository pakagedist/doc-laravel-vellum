<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Levanta un servidor HTTP local con el servidor PHP integrado.
 *
 * Sirve un directorio real (por ejemplo `public/`) para poder descargar
 * archivos estáticos tal y como los publicaría el servidor de producción.
 */
trait StartsHttpServer
{
    /**
     * @var resource|null
     */
    private $serverProcess = null;

    private int $serverPort = 0;

    /**
     * Arranca el servidor y devuelve su URL base.
     */
    protected function startHttpServer(string $documentRoot): string
    {
        if ($this->serverPort !== 0) {
            return $this->serverBaseUrl();
        }

        if (! function_exists('curl_init')) {
            $this->markTestSkipped('The cURL extension is not available.');
        }

        $this->serverPort = $this->freePort();

        $command = sprintf(
            'exec %s -S 127.0.0.1:%d -t %s',
            escapeshellarg(PHP_BINARY),
            $this->serverPort,
            escapeshellarg($documentRoot),
        );

        $process = proc_open(
            $command,
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w'],
            ],
            $pipes,
        );

        if (! is_resource($process)) {
            $this->markTestSkipped('The local HTTP server could not be started.');
        }

        $this->serverProcess = $process;

        $this->waitForTheServer();

        return $this->serverBaseUrl();
    }

    /**
     * URL base del servidor local.
     */
    protected function serverBaseUrl(): string
    {
        return sprintf('http://127.0.0.1:%d', $this->serverPort);
    }

    /**
     * Detiene el servidor local.
     */
    protected function stopHttpServer(): void
    {
        if (is_resource($this->serverProcess)) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
        }

        $this->serverProcess = null;
        $this->serverPort = 0;
    }

    /**
     * Reserva un puerto libre.
     */
    private function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

        if ($socket === false) {
            $this->markTestSkipped('Unable to reserve a local port.');
        }

        $name = (string) stream_socket_get_name($socket, false);

        fclose($socket);

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }

    /**
     * Espera a que el servidor acepte conexiones.
     */
    private function waitForTheServer(): void
    {
        $deadline = microtime(true) + 10;

        while (microtime(true) < $deadline) {
            $connection = @fsockopen('127.0.0.1', $this->serverPort, $errno, $errstr, 0.2);

            if (is_resource($connection)) {
                fclose($connection);

                return;
            }

            usleep(50000);
        }

        $this->markTestSkipped('The local HTTP server did not start in time.');
    }
}
