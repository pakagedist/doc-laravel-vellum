@extends('showcase.layout')

@section('title', 'Índice del panel')

@section('crumbs')
    <a href="{{ route('showcase.home') }}">Inicio</a>
    <span class="sep">/</span>
    <span class="here">Índice del panel</span>
@endsection

@section('content')
    @php
        $totalVariants = collect($categories)->sum(
            fn ($category) => collect($category->demos)->sum(fn ($demo) => count($demo->variants))
        );
        $activeEngines = collect($engines)->where('state', 'ok')->count();
    @endphp

    <div class="page-head">
        <h1>Panel de demostración</h1>
        <p class="lead">
            Vista técnica de la instalación: el estado real de cada motor y el índice completo de
            demostraciones. Todo se ejecuta en el momento con el motor por defecto
            (<strong class="mono">{{ $defaultDriver }}</strong>).
        </p>
        <div class="meta">
            <span class="pill"><span class="dot ok"></span>{{ $activeEngines }} de {{ count($engines) }} motores activos</span>
            <span class="pill">{{ count($categories) }} categorías</span>
            <span class="pill">{{ collect($categories)->sum(fn ($category) => count($category->demos)) }} demostraciones</span>
            <span class="pill">{{ $totalVariants }} variantes</span>
        </div>
    </div>

    <div class="section-head">
        <div>
            <h2>Motores detectados</h2>
            <p>Resultado de renderizar un documento mínimo con cada motor configurado.</p>
        </div>
        <code class="hint">php artisan pdf:check</code>
    </div>

    <div class="table-wrap" style="margin-bottom: 8px">
        <table>
            <thead>
            <tr>
                <th style="width: 150px">Motor</th>
                <th style="width: 150px">Estado</th>
                <th style="width: 90px">Tamaño</th>
                <th style="width: 90px">Tiempo</th>
                <th style="width: 90px">Avisos</th>
                <th>Detalle</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($engines as $engine)
                <tr>
                    <td class="mono">{{ $engine->driver }}</td>
                    <td>
                        <span class="badge {{ $engine->state }}"><span class="dot"></span>{{ $engine->label() }}</span>
                    </td>
                    <td class="small">{{ $engine->isOk() ? number_format($engine->size / 1024, 1).' KB' : '—' }}</td>
                    <td class="small">{{ $engine->isOk() ? number_format($engine->durationMs, 0).' ms' : '—' }}</td>
                    <td class="small">{{ $engine->isOk() ? $engine->warningCount : '—' }}</td>
                    <td class="small muted">{{ $engine->message ?: 'Sin incidencias.' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="section-head">
        <div>
            <h2>Demostraciones</h2>
            <p>Cada tarjeta abre una categoría; dentro, cada demostración tiene sus variantes.</p>
        </div>
    </div>

    <div class="grid cols-2">
        @foreach ($categories as $category)
            <div class="card demo-card">
                <div class="cat-top" style="display: flex; gap: 12px; align-items: flex-start">
                    <span class="cat-icon" aria-hidden="true">
                        @include('showcase.partials.category-icon', ['slug' => $category->slug])
                    </span>
                    <div>
                        <h3><a href="{{ route('pdf.category', $category->slug) }}">{{ $category->title }}</a></h3>
                        <p class="small muted" style="margin: 0">{{ $category->summary }}</p>
                    </div>
                </div>

                <ul style="margin: 2px 0 0; padding-left: 18px">
                    @foreach ($category->demos as $demo)
                        <li class="small" style="margin: 4px 0">
                            <a href="{{ route('pdf.demo', [$category->slug, $demo->slug]) }}">{{ $demo->title }}</a>
                            <span class="chip" style="margin-left: 4px">{{ count($demo->variants) }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="demo-foot">
                    <span class="small muted">{{ count($category->demos) }} demostraciones</span>
                    <a class="small" href="{{ route('pdf.category', $category->slug) }}" style="text-decoration: none; color: var(--accent-ink); font-weight: 600">
                        Explorar categoría →
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="section-head">
        <div>
            <h2>Documentos de la aplicación</h2>
            <p>Los documentos que genera la propia aplicación con <code>App\Documents\DocumentRenderer</code>.</p>
        </div>
    </div>

    <div class="card">
        <ul style="margin: 0; padding-left: 18px">
            <li class="small" style="margin: 5px 0">
                <a href="{{ route('pdf.users') }}">Listado de usuarios</a> — descarga en A4 vertical (motor por defecto).
            </li>
            <li class="small" style="margin: 5px 0">
                <a href="{{ route('pdf.users', ['driver' => 'mpdf']) }}">El mismo documento con mPDF</a> — mismo Blade, otro motor.
            </li>
            <li class="small" style="margin: 5px 0">
                <a href="{{ route('pdf.users', ['driver' => 'browsershot']) }}">El mismo documento con Chromium</a> — motor de Browsershot.
            </li>
            <li class="small" style="margin: 5px 0">
                <a href="{{ route('pdf.users', ['inline' => 1]) }}">Servido en línea</a> — se abre en el navegador en vez de descargarse.
            </li>
            <li class="small" style="margin: 5px 0">
                <a href="{{ route('pdf.users', ['orientation' => 'landscape']) }}">A4 apaisado</a> — la orientación sale del documento.
            </li>
            <li class="small" style="margin: 5px 0">
                <a href="{{ route('pdf.users', ['paper' => 'a5']) }}">A5</a> — otro tamaño de papel.
            </li>
            <li class="small" style="margin: 5px 0">
                <a href="{{ route('pdf.inspect') }}">Métricas del render (JSON)</a> — tamaño, páginas, MediaBox, sha256 y avisos.
            </li>
            <li class="small" style="margin: 5px 0">
                <a href="{{ route('documents.users') }}">Ejemplo de la aplicación</a> — el mismo documento servido desde el controlador de la app.
            </li>
        </ul>
    </div>

    <div class="section-head">
        <div>
            <h2>Cómo se usa en la aplicación</h2>
            <p>El punto de entrada es <code>App\Documents\DocumentRenderer</code>: recibe una vista Blade y unas opciones, y devuelve el PDF ya renderizado.</p>
        </div>
    </div>

    <div class="code-block">
        <div class="code-head">
            <span>PHP</span>
            <button type="button" class="copy" data-copy>Copiar</button>
        </div>
        <pre><code>use App\Documents\DocumentOptions;
use App\Documents\DocumentRenderer;

return $this->renderer->download('documents.users', ['users' => $users], new DocumentOptions(
    driver: 'mpdf',
    orientation: 'landscape',
    filename: 'usuarios-'.now()->format('Y-m-d').'.pdf',
    metadata: ['title' => 'Listado de usuarios'],
));</code></pre>
    </div>

    <p class="note">
        El papel y la orientación los decide el documento, no el CSS: las reglas
        <code>@page { size: … }</code> de la plantilla se descartan y, si no coinciden con lo configurado,
        el motor emite un aviso en lugar de cambiar el papel en silencio.
    </p>
@endsection
