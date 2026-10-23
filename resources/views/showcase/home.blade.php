@extends('showcase.layout')

@section('title', 'Inicio')

@section('crumbs')
    <span class="here">Inicio</span>
@endsection

@section('content')
    @php
        $totalVariants = collect($categories)->sum(
            fn ($category) => collect($category->demos)->sum(fn ($demo) => count($demo->variants))
        );
        $activeEngines = collect($engines)->where('state', 'ok')->count();
    @endphp

    <section class="hero">
        <span class="eyebrow">Documentación ejecutable · Laravel {{ app()->version() }}</span>
        <h1>laravel-vellum</h1>
        <p>
            La capa de generación de PDF independiente del motor. Aquí no hay capturas ni resultados
            prefabricados: cada categoría de la API se ejecuta de verdad y muestra su código, el PDF
            resultante y sus métricas.
        </p>
        <div class="actions">
            <a class="btn" href="#categorias">Explorar las categorías</a>
            <a class="btn ghost" href="{{ route('pdf.index') }}">Índice técnico</a>
            <a class="btn ghost" href="{{ route('pdf.users', ['inline' => 1]) }}" target="_blank" rel="noopener">
                Ver un PDF de ejemplo
            </a>
        </div>
    </section>

    <div class="stats">
        <div class="stat">
            <span class="k">Categorías</span>
            <span class="v">{{ count($categories) }}</span>
            <span class="hint">Bloques de la API</span>
        </div>
        <div class="stat">
            <span class="k">Demostraciones</span>
            <span class="v">{{ $totalDemos }}</span>
            <span class="hint">Ejecutables en vivo</span>
        </div>
        <div class="stat">
            <span class="k">Variantes</span>
            <span class="v">{{ $totalVariants }}</span>
            <span class="hint">Casos concretos</span>
        </div>
        <div class="stat">
            <span class="k">Motores activos</span>
            <span class="v">{{ $activeEngines }} <small>/ {{ count($engines) }}</small></span>
            <span class="hint">Por defecto: <code>{{ $defaultDriver }}</code></span>
        </div>
    </div>

    <div class="section-head">
        <div>
            <h2>Motores disponibles</h2>
            <p>Comprobados en esta instalación renderizando un documento mínimo.</p>
        </div>
        <a class="hint" href="{{ route('pdf.category', 'motores') }}">Comparar motores →</a>
    </div>

    <div class="grid cols-4">
        @foreach ($engines as $engine)
            <div class="card engine">
                <div class="top">
                    <span class="name">{{ $engine->driver }}</span>
                    <span class="badge {{ $engine->state }}">
                        <span class="dot"></span>{{ $engine->label() }}
                    </span>
                </div>
                @if ($engine->isOk())
                    <div class="metrics">
                        <span><strong>{{ number_format($engine->size / 1024, 1) }}</strong> KB</span>
                        <span><strong>{{ number_format($engine->durationMs, 0) }}</strong> ms</span>
                        <span><strong>{{ $engine->warningCount }}</strong> avisos</span>
                    </div>
                @elseif ($engine->state === 'missing')
                    <p class="small muted" style="margin: 0">No instalado en esta aplicación.</p>
                @else
                    <p class="small muted" style="margin: 0">{{ $engine->message ?: 'No disponible.' }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="section-head" id="categorias">
        <div>
            <h2>Categorías de la API</h2>
            <p>Siete bloques que cubren todo el ciclo de vida de un documento PDF.</p>
        </div>
        <span class="hint">{{ $totalDemos }} demos · {{ $totalVariants }} variantes</span>
    </div>

    <div class="grid cols-2">
        @foreach ($categories as $category)
            @php
                $demoCount = count($category->demos);
                $variantCount = collect($category->demos)->sum(fn ($demo) => count($demo->variants));
            @endphp
            <a class="cat-card" href="{{ route('pdf.category', $category->slug) }}">
                <div class="cat-top">
                    <span class="cat-icon" aria-hidden="true">
                        @include('showcase.partials.category-icon', ['slug' => $category->slug])
                    </span>
                    <div>
                        <h3>{{ $category->title }}</h3>
                        <p>{{ $category->summary }}</p>
                    </div>
                </div>
                <div class="cat-foot">
                    <span>{{ $demoCount }} {{ $demoCount === 1 ? 'demostración' : 'demostraciones' }} · {{ $variantCount }} {{ $variantCount === 1 ? 'variante' : 'variantes' }}</span>
                    <span class="arrow" aria-hidden="true">Explorar →</span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="section-head">
        <div>
            <h2>Documentos de la aplicación</h2>
            <p>El mismo paquete usado desde <code>App\Documents\DocumentRenderer</code>, con opciones por query string.</p>
        </div>
        <a class="hint" href="{{ route('pdf.index') }}">Ver todos los ejemplos →</a>
    </div>

    <div class="grid cols-3">
        <a class="cat-card" href="{{ route('documents.users') }}">
            <div class="cat-top">
                <span class="cat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.9"/>
                    </svg>
                </span>
                <div>
                    <h3>Listado de usuarios</h3>
                    <p>Servido por el controlador de la aplicación, en A4 vertical.</p>
                </div>
            </div>
            <div class="cat-foot">
                <span class="mono-block">/documents/users</span>
                <span class="arrow" aria-hidden="true">Abrir →</span>
            </div>
        </a>

        <a class="cat-card" href="{{ route('pdf.users', ['driver' => 'mpdf']) }}">
            <div class="cat-top">
                <span class="cat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="6" y="6" width="12" height="12" rx="2"/>
                        <path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/>
                    </svg>
                </span>
                <div>
                    <h3>El mismo PDF con mPDF</h3>
                    <p>La plantilla no cambia: solo el motor que la renderiza.</p>
                </div>
            </div>
            <div class="cat-foot">
                <span class="mono-block">?driver=mpdf</span>
                <span class="arrow" aria-hidden="true">Abrir →</span>
            </div>
        </a>

        <a class="cat-card" href="{{ route('pdf.inspect') }}">
            <div class="cat-top">
                <span class="cat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>
                    </svg>
                </span>
                <div>
                    <h3>Métricas del render</h3>
                    <p>Tamaño, páginas, MediaBox, sha256 y avisos, en JSON.</p>
                </div>
            </div>
            <div class="cat-foot">
                <span class="mono-block">/pdf/inspect</span>
                <span class="arrow" aria-hidden="true">Abrir →</span>
            </div>
        </a>
    </div>

    <div class="section-head">
        <div>
            <h2>Catálogo completo</h2>
            <p>Las {{ $totalDemos }} demostraciones, una por fila, con sus variantes.</p>
        </div>
    </div>

    <details class="panel">
        <summary>
            Ver el catálogo completo
            <span class="chip">{{ $totalDemos }} demos · {{ $totalVariants }} variantes</span>
        </summary>
        <div class="panel-body">
            <table>
                <thead>
                <tr>
                    <th style="width: 20%">Categoría</th>
                    <th style="width: 22%">Demostración</th>
                    <th>Qué demuestra</th>
                    <th class="right" style="width: 80px">Variantes</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($categories as $category)
                    @foreach ($category->demos as $demo)
                        <tr>
                            <td class="small">
                                <a href="{{ route('pdf.category', $category->slug) }}" class="muted">{{ $category->title }}</a>
                            </td>
                            <td class="small">
                                <a href="{{ route('pdf.demo', [$category->slug, $demo->slug]) }}"><strong>{{ $demo->title }}</strong></a>
                            </td>
                            <td class="small muted">{{ $demo->summary }}</td>
                            <td class="right small muted">{{ count($demo->variants) }}</td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
    </details>

    <div class="section-head">
        <div>
            <h2>Qué hay en esta aplicación</h2>
            <p>Las rutas que responden a una petición, con su nombre. El panel de PDF es una de ellas.</p>
        </div>
    </div>

    <details class="panel">
        <summary>
            Ver las rutas de la aplicación
            <span class="chip">{{ count($routes) }} rutas</span>
        </summary>
        <div class="panel-body">
            <table>
                <thead>
                <tr>
                    <th style="width: 90px">Método</th>
                    <th style="width: 42%">URI</th>
                    <th>Nombre</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($routes as $route)
                    <tr>
                        <td class="mono small muted">{{ $route['method'] }}</td>
                        <td class="mono small">{{ $route['uri'] }}</td>
                        <td class="small muted">{{ $route['name'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endsection
