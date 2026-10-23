<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura['numero'] }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #1f2933;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.5;
        }

        .cabecera {
            display: table;
            width: 100%;
            border-bottom: 2px solid #1f2933;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .emisor, .factura { display: table-cell; width: 50%; vertical-align: top; }
        .factura { text-align: right; }

        h1 { margin: 0 0 2px; font-size: 20px; }
        .muted { color: #616e7c; }
        .dato { margin: 1px 0; }

        .titulo { font-size: 15px; font-weight: bold; margin: 0 0 10px; }

        table.lineas { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.lineas th {
            background: #1f2933;
            color: #ffffff;
            text-align: left;
            padding: 7px 10px;
            font-size: 10px;
        }
        table.lineas td { border-bottom: 1px solid #e4e7eb; padding: 7px 10px; }
        table.lineas td.num { text-align: right; white-space: nowrap; }

        .totales { width: 45%; margin-left: auto; border-collapse: collapse; }
        .totales td { padding: 4px 10px; }
        .totales td.num { text-align: right; white-space: nowrap; }
        .totales tr.total td {
            border-top: 2px solid #1f2933;
            font-weight: bold;
            font-size: 14px;
            padding-top: 8px;
        }

        .condiciones {
            margin-top: 22px;
            padding: 10px 12px;
            background: #f5f7fa;
            border-left: 3px solid #0b5cad;
            color: #3e4c59;
        }

        footer {
            margin-top: 22px;
            padding-top: 8px;
            border-top: 1px solid #cbd2d9;
            color: #616e7c;
            font-size: 9px;
            display: table;
            width: 100%;
        }
        footer div { display: table-cell; width: 50%; }
        footer div:last-child { text-align: right; }
    </style>
</head>
<body>
<div class="cabecera">
    <div class="emisor">
        <h1>{{ $factura['empresa']['nombre'] }}</h1>
        <p class="dato muted">CIF {{ $factura['empresa']['cif'] }}</p>
        <p class="dato muted">{{ $factura['empresa']['direccion'] }}</p>
        <p class="dato muted">{{ $factura['empresa']['email'] }} · {{ $factura['empresa']['telefono'] }}</p>
    </div>
    <div class="factura">
        <p class="titulo">Factura {{ $factura['numero'] }}</p>
        <p class="dato muted">Fecha de emisión: {{ $factura['fecha'] }}</p>
        <p class="dato muted">Vencimiento: {{ $factura['vencimiento'] }}</p>
    </div>
</div>

<p class="titulo">Facturar a</p>
<p class="dato"><strong>{{ $factura['cliente']['nombre'] }}</strong></p>
<p class="dato muted">CIF {{ $factura['cliente']['cif'] }}</p>
<p class="dato muted">{{ $factura['cliente']['direccion'] }}</p>
<p class="dato muted">{{ $factura['cliente']['email'] }}</p>

<p class="titulo" style="margin-top: 18px">Conceptos</p>
<table class="lineas">
    <thead>
    <tr>
        <th style="width: 56%">Concepto</th>
        <th style="width: 12%">Cantidad</th>
        <th style="width: 16%">Precio</th>
        <th style="width: 16%">Importe</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($factura['lineas'] as $linea)
        <tr>
            <td>{{ $linea['concepto'] }}</td>
            <td class="num">{{ number_format($linea['cantidad'], 0, ',', '.') }}</td>
            <td class="num">{{ number_format($linea['precio'], 2, ',', '.') }} {{ $factura['moneda'] }}</td>
            <td class="num">{{ number_format($linea['importe'], 2, ',', '.') }} {{ $factura['moneda'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totales">
    <tr>
        <td class="muted">Base imponible</td>
        <td class="num">{{ number_format($factura['subtotal'], 2, ',', '.') }} {{ $factura['moneda'] }}</td>
    </tr>
    <tr>
        <td class="muted">IVA (21 %)</td>
        <td class="num">{{ number_format($factura['impuesto'], 2, ',', '.') }} {{ $factura['moneda'] }}</td>
    </tr>
    <tr class="total">
        <td>Total</td>
        <td class="num">{{ number_format($factura['total'], 2, ',', '.') }} {{ $factura['moneda'] }}</td>
    </tr>
</table>

<div class="condiciones">
    Pago por transferencia a 30 días. Esta factura se ha generado con la capa de PDF de Vellum,
    a partir de una vista Blade.
</div>

<footer>
    <div>{{ $factura['empresa']['nombre'] }} · CIF {{ $factura['empresa']['cif'] }}</div>
    <div>Documento generado el {{ now()->format('d/m/Y H:i') }}</div>
</footer>
</body>
</html>
