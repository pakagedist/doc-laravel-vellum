<?php

declare(strict_types=1);

namespace App\Documents;

/**
 * El lanzador de Chromium que usa browsershot.
 *
 * Chrome no arranca como root sin `--no-sandbox`, y en los contenedores el
 * proceso suele serlo. En ese caso se genera un lanzador que añade los
 * argumentos que necesita el contenedor y delega en el navegador real.
 */
final class ChromeLauncher
{
    /**
     * La ruta que browsershot debe usar como navegador.
     *
     * @param  string  $chrome  Ruta del navegador real.
     * @param  bool  $asRoot  Si el proceso se ejecuta como root.
     */
    public function path(string $chrome, bool $asRoot): string
    {
        if (! $asRoot) {
            return $chrome;
        }

        $folder = storage_path('framework/browsershot');

        if (! is_dir($folder) && ! @mkdir($folder, 0775, true) && ! is_dir($folder)) {
            return $chrome;
        }

        $launcher = $folder.'/chrome';

        $script = <<<'SH'
            #!/bin/sh
            exec '%s' --no-sandbox --disable-dev-shm-usage "$@"
            SH;

        $script = sprintf($script, str_replace("'", "'\\''", $chrome));

        if (@file_put_contents($launcher, $script."\n") === false) {
            return $chrome;
        }

        @chmod($launcher, 0755);

        return $launcher;
    }
}
