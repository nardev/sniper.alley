@extends('layouts.site')
@php
    use App\Content;
    $slug = $item['slug'];
    $banner = Content::image('memoriam', $slug, $item['banner'] ?? null);
    $cover = Content::image('memoriam', $slug, $item['cover'] ?? null);
    $photographer = Content::photographer($item['photographer'] ?? null);
@endphp
@section('main')
    <section class="relative overflow-hidden bg-night text-white">
        @if ($banner)
            <img src="{{ asset($banner) }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager">
            <div class="absolute inset-0 bg-gradient-to-t from-night via-night/70 to-night/30"></div>
        @endif
        <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 {{ $banner ? 'pt-24 lg:pt-56' : '' }}">
            <nav class="text-xs text-white/60" aria-label="Breadcrumb">
                <a href="{{ route('index') }}" class="hover:text-white">Home</a>
                <span class="mx-1">/</span>
                <a href="{{ route('in-memoriam') }}" class="hover:text-white">In Memoriam</a>
                <span class="mx-1">/</span>
                <span class="text-white/90">{{ $item['name'] }}</span>
            </nav>
            <div class="mt-8 flex flex-col items-start gap-8 sm:flex-row">
                @if (! $banner && $cover)
                    <img src="{{ asset($cover) }}" alt="{{ $item['name'] }}" class="w-full max-w-xs object-cover sm:w-64">
                @endif
                <div>
                    <p class="kicker">In Memoriam</p>
                    <h1 class="mt-2 font-display text-4xl font-bold sm:text-5xl">{{ $item['name'] }}</h1>
                    <p class="mt-2 text-sm tracking-[0.2em] text-white/60">{{ $item['born'] ?? '' }} - {{ $item['died'] ?? '' }}</p>
                    @if ($item['excerpt'] ?? false)
                        <p class="mt-5 max-w-xl font-display text-xl italic leading-relaxed text-white/90">{{ $item['excerpt'] }}</p>
                    @endif
                    @if ($photographer)
                        <a href="{{ route('photographers/'.$photographer['slug']) }}" class="btn-outline mt-6 text-white">View photo gallery</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if (trim($item['body'] ?? '') !== '')
        <article class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
            <div class="prose-site">{!! Content::renderMarkdown($item['body']) !!}</div>
        </article>
    @endif

    @include('components.cta-band', [
        'heading' => 'Preserving stories. Honoring humanity.',
        'text' => 'Their work remains. Help us keep it in the light.',
    ])
@endsection
