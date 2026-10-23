<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listado de usuarios</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #1f2933;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.5;
        }

        header {
            border-bottom: 2px solid #1f2933;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        .muted { color: #616e7c; }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .summary td {
            border: 1px solid #cbd2d9;
            padding: 8px 10px;
            width: 25%;
            vertical-align: top;
        }

        .summary .label {
            display: block;
            color: #616e7c;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .summary .value {
            display: block;
            font-size: 16px;
            font-weight: bold;
        }

        table.records {
            width: 100%;
            border-collapse: collapse;
        }

        table.records th {
            background: #1f2933;
            color: #ffffff;
            text-align: left;
            padding: 7px 10px;
            font-size: 10px;
        }

        table.records td {
            border-bottom: 1px solid #e4e7eb;
            padding: 7px 10px;
        }

        table.records tr.odd td { background: #f5f7fa; }

        footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #cbd2d9;
            color: #616e7c;
            font-size: 9px;
            text-align: right;
        }
    </style>
</head>
<body>
<header>
    <h1>Listado de usuarios</h1>
    <span class="muted">{{ config('app.name') }}</span>
</header>

<table class="summary">
    <tr>
        <td>
            <span class="label">Usuarios</span>
            <span class="value">{{ $users->count() }}</span>
        </td>
        <td>
            <span class="label">Verificados</span>
            <span class="value">{{ $users->whereNotNull('email_verified_at')->count() }}</span>
        </td>
        <td>
            <span class="label">Alta más reciente</span>
            <span class="value">{{ $users->max('created_at')?->format('d/m/Y') ?? '—' }}</span>
        </td>
        <td>
            <span class="label">Generado</span>
            <span class="value">{{ $generatedAt->format('d/m/Y H:i') }}</span>
        </td>
    </tr>
</table>

<table class="records">
    <thead>
    <tr>
        <th style="width: 45%">Nombre</th>
        <th style="width: 40%">Correo</th>
        <th style="width: 15%">Alta</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($users as $user)
        <tr class="{{ $loop->odd ? 'odd' : '' }}">
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->created_at?->format('d/m/Y') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3" style="text-align: center; color: #616e7c">No hay usuarios registrados.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<footer>
    {{ config('app.name') }} · Documento generado el {{ $generatedAt->format('d/m/Y \a \l\a\s H:i') }}
</footer>
</body>
</html>
