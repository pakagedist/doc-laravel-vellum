<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Documents\EngineProbe;
use Illuminate\Console\Command;

/**
 * Comprueba que los motores de PDF disponibles en la aplicación son capaces
 * de generar un documento.
 */
final class PdfCheckCommand extends Command
{
    protected $signature = 'pdf:check
                            {--driver=* : Motor a comprobar (por defecto, todos los configurados)}';

    protected $description = 'Comprueba que los motores de PDF de la aplicación generan documentos correctamente';

    public function handle(EngineProbe $probe): int
    {
        $drivers = (array) $this->option('driver');

        if ($drivers === []) {
            $drivers = array_keys((array) config('pdf.drivers', []));
        }

        $rows = [];
        $failed = false;

        foreach ($drivers as $driver) {
            $status = $probe->probe((string) $driver);

            $failed = $failed || $status->state === 'error';

            $rows[] = [
                $status->driver,
                $this->label($status->state),
                $status->isOk() ? number_format($status->size / 1024, 1) : '0',
                $status->isOk() ? number_format($status->durationMs, 1) : '0',
                $status->isOk() ? (string) $status->warningCount : '0',
                $status->message,
            ];
        }

        $this->table(['Motor', 'Estado', 'KB', 'ms', 'Avisos', 'Detalle'], $rows);

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function label(string $state): string
    {
        return match ($state) {
            'ok' => '<fg=green>OK</>',
            'missing' => '<fg=yellow>NO INSTALADO</>',
            default => '<fg=red>ERROR</>',
        };
    }
}
