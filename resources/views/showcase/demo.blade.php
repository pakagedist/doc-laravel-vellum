@extends('showcase.layout')

@section('title', $demo->title)

@section('crumbs')
    <a href="{{ route('showcase.home') }}">Inicio</a>
    <span class="sep">/</span>
    <a href="{{ route('pdf.index') }}">Índice del panel</a>
    <span class="sep">/</span>
    <a href="{{ route('pdf.category', $category->slug) }}">{{ $category->title }}</a>
    <span class="sep">/</span>
    <span class="here">{{ $demo->title }}</span>
@endsection

@section('content')
    @php
        $metrics = collect($outcome->metrics);
        $value = fn (string $label): ?string => $metrics->firstWhere('label', $label)['value'] ?? null;
    @endphp

    <div class="page-head">
        <h1>{{ $demo->title }}</h1>
        <p class="lead">{{ $demo->summary }}</p>
        <div class="meta">
            <a class="pill" href="{{ route('pdf.category', $category->slug) }}" style="text-decoration: none">
                @include('showcase.partials.category-icon', ['slug' => $category->slug])
                {{ $category->title }}
            </a>
            @if ($value('Motor'))
                <span class="pill">motor <code>{{ $value('Motor') }}</code></span>
            @endif
            @if ($value('Fuente'))
                <span class="pill">fuente <code>{{ $value('Fuente') }}</code></span>
            @endif
            @if ($value('Tamaño'))
                <span class="pill">{{ $value('Tamaño') }}</span>
            @endif
            @if ($value('Duración'))
                <span class="pill">{{ $value('Duración') }}</span>
            @endif
            @if ($value('Páginas'))
                <span class="pill">{{ $value('Páginas') }} {{ $value('Páginas') === '1' ? 'página' : 'páginas' }}</span>
            @endif
        </div>
    </div>

    @if (count($demo->variants) > 1)
        <div class="section-head" style="margin-top: 0">
            <div>
                <h2>Variantes</h2>
                <p>{{ $variant->description ?: 'Cada variante cambia un aspecto del documento.' }}</p>
            </div>
        </div>
        <div class="variants" style="margin-bottom: 18px">
            @foreach ($demo->variants as $option)
                <a href="{{ route('pdf.demo.variant', [$category->slug, $demo->slug, $option->key]) }}"
                   @class(['active' => $option->key === $variant->key])
                   title="{{ $option->description }}">
                    {{ $option->label }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="actions">
        <a class="btn" href="{{ route('pdf.preview', [$category->slug, $demo->slug, $variant->key]) }}" target="_blank" rel="noopener">
            Ver en línea
        </a>
        <a class="btn ghost" href="{{ route('pdf.download', [$category->slug, $demo->slug, $variant->key]) }}">
            Descargar PDF
        </a>
        @foreach ($outcome->links as $link)
            <a class="btn ghost" href="{{ $link['url'] }}"
               @if (($link['kind'] ?? '') !== 'internal') target="_blank" rel="noopener" @endif>{{ $link['label'] }}</a>
        @endforeach
    </div>

    @if ($outcome->state === 'unavailable')
        <div class="alert missing">
            <strong>Esta demostración no se puede ejecutar aquí</strong>
            {{ $outcome->message }}
        </div>
    @elseif ($outcome->state === 'error')
        <div class="alert error">
            <strong>La demostración ha fallado</strong>
            {{ $outcome->message }}
        </div>
    @endif

    @if ($outcome->hasPreview())
        <div class="preview">
            <header>
                <span class="name">Resultado · {{ $demo->title }}</span>
                <span class="mono">{{ $category->slug }}/{{ $demo->slug }}/{{ $variant->key }}</span>
            </header>
            <iframe src="{{ route('pdf.preview', [$category->slug, $demo->slug, $variant->key]) }}"
                    title="Vista previa del PDF"></iframe>
        </div>
    @endif

    @if ($outcome->metrics !== [])
        <div class="section-head" style="margin-top: 26px">
            <div>
                <h2>Métricas del render</h2>
                <p>Datos medidos sobre el PDF generado en esta petición.</p>
            </div>
        </div>
        <div class="metrics" style="margin-bottom: 22px">
            @foreach ($outcome->metrics as $metric)
                <div class="metric">
                    <span class="k">{{ $metric['label'] }}</span>
                    <span class="v mono">{{ $metric['value'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($outcome->notes !== [])
        <div class="section-head" style="margin-top: 26px">
            <div>
                <h2>Detalles</h2>
                <p>Lo que conviene saber de este caso.</p>
            </div>
        </div>
        @foreach ($outcome->notes as $note)
            <p class="note">{{ $note }}</p>
        @endforeach
    @endif

    <div class="section-head" style="margin-top: 26px">
        <div>
            <h2>Código</h2>
            <p>Lo mínimo necesario para producir este documento.</p>
        </div>
    </div>

    <div class="code-block">
        <div class="code-head">
            <span>PHP</span>
            <button type="button" class="copy" data-copy>Copiar</button>
        </div>
        <pre><code>{{ $demo->code }}</code></pre>
    </div>
@endsection
