<?php

declare(strict_types=1);

namespace App\Showcase;

use Throwable;
use Vellum\Exceptions\DriverNotSupportedException;
use Vellum\Exceptions\PdfGenerationException;
use Vellum\Exceptions\SourceNotFoundException;

/**
 * Ejecuta una demostración y convierte sus fallos en algo que se pueda
 * mostrar en pantalla.
 */
final class ShowcaseRunner
{
    /**
     * Ejecuta la demostración en la variante indicada.
     */
    public function run(ShowcaseDemo $demo, ShowcaseVariant $variant): ShowcaseOutcome
    {
        try {
            return ($demo->handler)($variant);
        } catch (DriverNotSupportedException $exception) {
            return ShowcaseOutcome::unavailable($exception->getMessage());
        } catch (SourceNotFoundException $exception) {
            return ShowcaseOutcome::unavailable($exception->getMessage());
        } catch (PdfGenerationException $exception) {
            return ShowcaseOutcome::error($exception->getMessage());
        } catch (Throwable $exception) {
            return ShowcaseOutcome::error($exception->getMessage());
        }
    }
}
