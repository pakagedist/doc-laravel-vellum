<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;

class PdfCheckCommandTest extends TestCase
{
    public function test_command_reports_every_configured_driver(): void
    {
        $this->artisan('pdf:check')
            ->expectsOutputToContain('dompdf')
            ->expectsOutputToContain('mpdf')
            ->assertSuccessful();
    }

    public function test_command_checks_only_the_requested_driver(): void
    {
        $this->artisan('pdf:check --driver=dompdf')
            ->expectsOutputToContain('dompdf')
            ->doesntExpectOutputToContain('mpdf')
            ->assertSuccessful();
    }
}
