<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Espécimen tipográfico · {{ $familia }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #16181d;
            font-family: "{{ $familia }}", serif;
            font-size: 10pt;
            line-height: 1.5;
        }

        h1 {
            margin: 0 0 2mm;
            font-size: 22pt;
            line-height: 1.1;
            letter-spacing: -0.5pt;
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
            margin: 0 0 5mm;
            padding-bottom: 3mm;
            border-bottom: 0.4mm solid #16181d;
            color: #5b6270;
            font-size: 7.5pt;
        }

        .row { width: 100%; border-collapse: collapse; }

        .label {
            width: 30mm;
            padding: 1.6mm 0;
            color: #5b6270;
            font-size: 7.5pt;
            vertical-align: top;
        }

        .sample { padding: 1.6mm 0; vertical-align: top; }

        .alphabet { font-size: 13pt; line-height: 1.6; }
        .numbers { font-size: 13pt; letter-spacing: 0.4pt; }
        .accents { font-size: 12pt; }
        .small { font-size: 8pt; }

        .scala div { margin: 0 0 1mm; }

        .pangram {
            margin: 5mm 0 0;
            padding: 3.5mm 4mm;
            border-left: 1mm solid #b42318;
            background: #f6f7f9;
            font-size: 10.5pt;
            line-height: 1.6;
        }

        .table {
            width: 100%;
            margin-top: 5mm;
            border-collapse: collapse;
            font-size: 9pt;
        }

        .table th {
            padding: 2mm 0;
            border-bottom: 0.35mm solid #16181d;
            font-size: 7.5pt;
            letter-spacing: 0.6pt;
            text-align: left;
            text-transform: uppercase;
        }

        .table td { padding: 2mm 0; border-bottom: 0.25mm solid #d7dae0; }
        .table td:last-child, .table th:last-child { text-align: right; }

        .foot {
            margin-top: 6mm;
            color: #5b6270;
            font-size: 7.5pt;
        }
    </style>
</head>
<body>
    <p class="eyebrow">Espécimen tipográfico</p>
    <h1>Aa Bb Cc</h1>
    <p class="family">{{ $familia }}</p>
    <p class="meta">
        Origen: {{ $origen }}
        @isset($archivo)
            · Archivo: {{ $archivo }}
        @endisset
    </p>

    <table class="row">
        <tr>
            <td class="label">Mayúsculas</td>
            <td class="sample alphabet">ABCDEFGHIJKLMNOPQRSTUVWXYZ</td>
        </tr>
        <tr>
            <td class="label">Minúsculas</td>
            <td class="sample alphabet">abcdefghijklmnopqrstuvwxyz</td>
        </tr>
        <tr>
            <td class="label">Cifras</td>
            <td class="sample numbers">0123456789</td>
        </tr>
        <tr>
            <td class="label">Signos</td>
            <td class="sample">!@#$%&amp;*()[]{}/¿?¡! € — «» “”</td>
        </tr>
        <tr>
            <td class="label">Acentos</td>
            <td class="sample accents">áéíóú ÁÉÍÓÚ üÜ ñÑ çÇ</td>
        </tr>
    </table>

    <div class="scala">
        <div style="font-size: 18pt">18 pt · La tipografía también es jerarquía</div>
        <div style="font-size: 14pt">14 pt · Se lee mejor de lo que parece</div>
        <div style="font-size: 11pt">11 pt · El tamaño habitual de un cuerpo de texto</div>
        <div style="font-size: 9pt">9 pt · Para notas al pie y tablas densas</div>
    </div>

    <p class="pangram">
        El veloz murciélago hindú comía feliz cardillo y kiwi. La cigüeña tocaba el saxofón
        detrás del palenque de paja, mientras el pingüino cargaba su caña de pescar.
    </p>

    <table class="table">
        <thead>
        <tr>
            <th>Concepto</th>
            <th>Unidades</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Servicio de maquetación</td>
            <td>2</td>
            <td>480,00 €</td>
        </tr>
        <tr>
            <td>Revisión tipográfica</td>
            <td>1</td>
            <td>95,00 €</td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td></td>
            <td><strong>575,00 €</strong></td>
        </tr>
        </tbody>
    </table>

    <p class="foot">
        Generado con laravel-vellum · misma plantilla, solo cambia la familia tipográfica.
    </p>
</body>
</html>
