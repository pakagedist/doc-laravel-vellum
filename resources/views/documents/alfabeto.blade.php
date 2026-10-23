<!DOCTYPE html>
<html lang="{{ $idioma ?? 'es' }}">
<head>
    <meta charset="utf-8">
    <title>Alfabeto · {{ $familia }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #16181d;
            font-family: "{{ $familia }}", sans-serif;
            font-size: 10pt;
            line-height: 1.55;
        }

        h1 {
            margin: 0 0 2mm;
            font-size: 21pt;
            line-height: 1.15;
        }

        .eyebrow {
            margin: 0 0 3mm;
            color: #b42318;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1.6pt;
            text-transform: uppercase;
        }

        .family {
            margin: 0 0 1mm;
            font-size: 13pt;
            font-weight: bold;
        }

        .meta {
            margin: 0 0 6mm;
            padding-bottom: 3mm;
            border-bottom: 0.4mm solid #16181d;
            color: #5b6270;
            font-size: 7.5pt;
        }

        .block {
            margin: 0 0 5mm;
            padding: 4mm 5mm;
            border: 0.25mm solid #d7dae0;
            border-left: 1mm solid #b42318;
            background: #f9fafb;
        }

        .label {
            margin: 0 0 2mm;
            color: #5b6270;
            font-size: 7.5pt;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
        }

        .text {
            margin: 0;
            font-size: 15pt;
            line-height: 1.9;
        }

        .text.rtl { direction: rtl; text-align: right; }

        .small { font-size: 9pt; line-height: 1.6; }

        .latin {
            margin: 0;
            color: #5b6270;
            font-size: 8pt;
        }

        .foot {
            margin-top: 6mm;
            color: #5b6270;
            font-size: 7.5pt;
        }
    </style>
</head>
<body>
    <p class="eyebrow">Otros alfabetos</p>
    <h1>{{ $titulo }}</h1>
    <p class="family">{{ $familia }}</p>
    <p class="meta">Origen: {{ $origen }} · Cobertura: {{ $cobertura }}</p>

    @foreach ($textos as $texto)
        <div class="block">
            <p class="label">{{ $texto['label'] }}</p>
            <p class="text {{ ($texto['rtl'] ?? false) ? 'rtl' : '' }}">{{ $texto['text'] }}</p>
        </div>
    @endforeach

    <div class="block">
        <p class="label">Latino</p>
        <p class="latin">Aa Bb Cc 0123 · El veloz murciélago hindú comía feliz cardillo y kiwi.</p>
    </div>

    <p class="foot">
        Generado con laravel-vellum · misma plantilla para todos los alfabetos.
    </p>
</body>
</html>
