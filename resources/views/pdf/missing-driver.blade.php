<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vellum · motor no disponible</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem auto; max-width: 45rem; color: #222; line-height: 1.5; }
        code, pre { font-family: ui-monospace, monospace; font-size: .85rem; }
        pre { background: #f7f7f7; border: 1px solid #ddd; padding: .8rem; overflow-x: auto; }
        .muted { color: #666; }
        a { color: #0b5cad; }
    </style>
</head>
<body>
<h1>El motor «{{ $driver }}» no está disponible</h1>

<p>{{ $message }}</p>

@if ($driver === 'browsershot')
    <p>Este motor se puede activar en la aplicación así:</p>

    <pre><code>composer require spatie/browsershot
npm install puppeteer</code></pre>

    <p class="muted">
        Chromium necesita Node.js y una descarga de <code>chrome-headless-shell</code>.
        Con el motor instalado, vuelve a este enlace y el PDF se generará con Chromium.
    </p>
@else
    <p class="muted">
        Instala el paquete del motor que quieras usar en <code>config/pdf.php</code> y vuelve a este enlace.
    </p>
@endif

<p><a href="{{ route('pdf.index') }}">← Volver al panel de demostración</a></p>
</body>
</html>
