@extends('showcase.layout')

@section('title', $category->title)

@section('crumbs')
    <a href="{{ route('showcase.home') }}">Inicio</a>
    <span class="sep">/</span>
    <a href="{{ route('pdf.index') }}">Índice del panel</a>
    <span class="sep">/</span>
    <span class="here">{{ $category->title }}</span>
@endsection

@section('content')
    @php
        $variantCount = collect($category->demos)->sum(fn ($demo) => count($demo->variants));
    @endphp

    <div class="page-head">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px">
            <span class="cat-icon" aria-hidden="true">
                @include('showcase.partials.category-icon', ['slug' => $category->slug])
            </span>
            <span class="mono-block muted">{{ $category->slug }}</span>
        </div>
        <h1>{{ $category->title }}</h1>
        <p class="lead">{{ $category->summary }}</p>
        <div class="meta">
            <span class="pill">{{ count($category->demos) }} demostraciones</span>
            <span class="pill">{{ $variantCount }} variantes</span>
            <span class="pill">Motor por defecto: <code>{{ config('pdf.default_driver') }}</code></span>
        </div>
    </div>

    @if ($category->aside !== null)
        @include($category->aside['view'], $category->aside['data'] ?? [])
    @endif

    <div class="section-head">
        <div>
            <h2>Demostraciones</h2>
            <p>Abre una demostración para ver el PDF, su código y sus métricas.</p>
        </div>
        <span class="hint">{{ count($category->demos) }} demos · {{ $variantCount }} variantes</span>
    </div>

    <div class="grid cols-2">
        @foreach ($category->demos as $demo)
            <div class="card demo-card">
                <div class="demo-top">
                    <span class="demo-num">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3><a href="{{ route('pdf.demo', [$category->slug, $demo->slug]) }}">{{ $demo->title }}</a></h3>
                </div>
                <p class="small muted" style="margin: 0">{{ $demo->summary }}</p>

                <div class="variants">
                    @foreach ($demo->variants as $variant)
                        <a href="{{ route('pdf.demo.variant', [$category->slug, $demo->slug, $variant->key]) }}"
                           title="{{ $variant->description }}">
                            {{ $variant->label }}
                        </a>
                    @endforeach
                </div>

                <div class="demo-foot">
                    <span class="small muted">{{ count($demo->variants) }} {{ count($demo->variants) === 1 ? 'variante' : 'variantes' }}</span>
                    <a class="small" href="{{ route('pdf.demo', [$category->slug, $demo->slug]) }}"
                       style="text-decoration: none; color: var(--accent-ink); font-weight: 600">
                        Abrir demo →
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="section-head">
        <div>
            <h2>Sigue explorando</h2>
            <p>El resto de bloques de la API.</p>
        </div>
    </div>

    <div class="variants">
        @foreach ($categories as $other)
            @continue($other->slug === $category->slug)
            <a href="{{ route('pdf.category', $other->slug) }}">{{ $other->title }}</a>
        @endforeach
        <a href="{{ route('pdf.index') }}">Índice del panel</a>
    </div>
@endsection
