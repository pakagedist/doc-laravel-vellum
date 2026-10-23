<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Documents\PdfInspector;
use App\Showcase\Support\SampleData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;
use Vellum\Exceptions\DriverNotSupportedException;
use Vellum\Facades\Pdf;
use Vellum\PdfBuilder;

/**
 * Prueba de humo de la generación de PDF.
 *
 * Renderiza los documentos reales de la aplicación con cada motor y resume
 * tamaño, duración, páginas y avisos. Sirve para detectar un motor roto o una
 * plantilla que ha dejado de compilar sin abrir el panel.
 */
final class PdfSmokeCommand extends Command
{
    protected $signature = 'pdf:smoke
                            {--driver=* : Motor a probar (por defecto, todos los configurados)}
                            {--save : Guarda los PDF generados en storage/app/pdf-smoke}';

    protected $description = 'Renderiza los documentos de la aplicación con cada motor y muestra sus métricas';

    public function handle(): int
    {
        $configurados = array_keys((array) config('pdf.drivers', []));
        $drivers = (array) $this->option('driver');

        if ($drivers === []) {
            $drivers = $configurados;
        }

        $rows = [];
        $fallos = 0;

        foreach ($drivers as $driver) {
            $driver = (string) $driver;

            if (! in_array($driver, $configurados, true)) {
                $fallos++;
                $rows[] = [
                    'name' => '—',
                    'driver' => $driver,
                    'label' => '<fg=red>DESCONOCIDO</>',
                    'kb' => '0',
                    'ms' => '0',
                    'pages' => '0',
                    'warnings' => '0',
                    'detail' => 'No está configurado',
                    'state' => 'error',
                ];

                continue;
            }

            foreach ($this->samples() as $name => $sample) {
                $row = $this->render($name, $sample, $driver);
                $rows[] = $row;

                if ($row['state'] === 'error') {
                    $fallos++;
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Muestra', 'Motor', 'Estado', 'KB', 'ms', 'Páginas', 'Avisos', 'Detalle'],
            array_map(static fn (array $row): array => [
                $row['name'],
                $row['driver'],
                $row['label'],
                $row['kb'],
                $row['ms'],
                $row['pages'],
                $row['warnings'],
                $row['detail'],
            ], $rows),
        );

        if ($fallos > 0) {
            $this->error(sprintf('%d de %d renderizados han fallado.', $fallos, count($rows)));

            return self::FAILURE;
        }

        $this->info(sprintf('Los %d renderizados fueron correctos.', count($rows)));

        return self::SUCCESS;
    }

    /**
     * Documentos reales de la aplicación que se usan en la prueba.
     *
     * @return array<string, callable(): PdfBuilder>
     */
    private function samples(): array
    {
        return [
            'factura' => fn (): PdfBuilder => Pdf::view('documents.invoice', [
                'factura' => SampleData::invoice(),
            ])->paper('a4')->margins(12, 12, 14, 12),
            'informe' => fn (): PdfBuilder => Pdf::view('documents.informe', [
                'users' => SampleData::users(),
                'generatedAt' => now(),
            ])->paper('a4', 'landscape')->margins(10, 10, 10, 10),
            'html' => fn (): PdfBuilder => Pdf::html(
                '<h1>Prueba de humo</h1><p>HTML plano generado por <code>pdf:smoke</code>.</p>',
            )->paper('a5'),
        ];
    }

    /**
     * @param  callable(): PdfBuilder  $sample
     * @return array{name: string, driver: string, label: string, kb: string, ms: string, pages: string, warnings: string, detail: string, state: string}
     */
    private function render(string $name, callable $sample, string $driver): array
    {
        try {
            $result = $sample()->driver($driver)->render();

            if ((bool) $this->option('save')) {
                $result->save(storage_path('app/pdf-smoke/'.$name.'-'.$driver.'.pdf'));
            }

            $ok = str_starts_with($result->bytes, '%PDF-');

            return [
                'name' => $name,
                'driver' => $result->driver,
                'label' => $ok ? '<fg=green>OK</>' : '<fg=red>ERROR</>',
                'kb' => number_format($result->size() / 1024, 1),
                'ms' => number_format($result->durationMs, 1),
                'pages' => (string) PdfInspector::pageCount($result->bytes),
                'warnings' => (string) count($result->warnings),
                'detail' => $ok ? '' : 'El resultado no empieza por %PDF-',
                'state' => $ok ? 'ok' : 'error',
            ];
        } catch (DriverNotSupportedException $exception) {
            return [
                'name' => $name,
                'driver' => $driver,
                'label' => '<fg=yellow>NO INSTALADO</>',
                'kb' => '0',
                'ms' => '0',
                'pages' => '0',
                'warnings' => '0',
                'detail' => $exception->getMessage(),
                'state' => 'missing',
            ];
        } catch (Throwable $exception) {
            Log::error('pdf:smoke ha fallado', [
                'muestra' => $name,
                'motor' => $driver,
                'error' => $exception->getMessage(),
            ]);

            return [
                'name' => $name,
                'driver' => $driver,
                'label' => '<fg=red>ERROR</>',
                'kb' => '0',
                'ms' => '0',
                'pages' => '0',
                'warnings' => '0',
                'detail' => mb_substr($exception->getMessage(), 0, 120),
                'state' => 'error',
            ];
        }
    }
}
