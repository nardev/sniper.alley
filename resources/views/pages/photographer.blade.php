@extends('layouts.site')
@php
    use App\Content;
    $slug = $item['slug'];
    $portrait = Content::image('photographers', $slug, $item['portrait'] ?? null);
    $photos = collect($item['photos'] ?? [])
        ->map(fn ($photo) => $photo + ['path' => Content::image('photographers', $slug, $photo['file'] ?? null)])
        ->filter(fn ($photo) => $photo['path']);
    $dates = ($item['born'] ?? null) ? trim(($item['born'] ?? '').' - '.($item['died'] ?? ''), ' -') : null;
    $stories = Content::storiesBy($slug);
    $memorial = Content::memorial($item['memoriam'] ?? null);
@endphp
@section('main')
    <section class="bg-night text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            <nav class="text-xs text-white/50" aria-label="Breadcrumb">
                <a href="{{ route('index') }}" class="hover:text-white">Home</a>
                <span class="mx-1">/</span>
                <a href="{{ route('photographers') }}" class="hover:text-white">Photographers</a>
                <span class="mx-1">/</span>
                <span class="text-white/80">{{ $item['name'] }}</span>
            </nav>
            <div class="mt-8 grid items-start gap-10 lg:grid-cols-3">
                <div class="flex gap-6 lg:col-span-2">
                    @if ($portrait)
                        <img src="{{ asset($portrait) }}" alt="{{ $item['name'] }}" class="h-40 w-40 shrink-0 object-cover sm:h-52 sm:w-52">
                    @endif
                    <div>
                        <h1 class="font-display text-3xl font-bold sm:text-4xl">{{ $item['name'] }}</h1>
                        @if ($item['role'] ?? false)
                            <p class="mt-1 text-sm font-semibold uppercase tracking-widest text-accent">{{ $item['role'] }}</p>
                        @endif
                        <dl class="mt-4 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                            @if ($dates)
                                <div><dt class="text-[11px] uppercase tracking-widest text-white/40">Lived</dt><dd>{{ $dates }}</dd></div>
                            @endif
                            @if ($item['country'] ?? false)
                                <div><dt class="text-[11px] uppercase tracking-widest text-white/40">From</dt><dd>{{ $item['country'] }}</dd></div>
                            @endif
                            <div><dt class="text-[11px] uppercase tracking-widest text-white/40">Gallery</dt><dd>{{ $photos->count() }} photos</dd></div>
                        </dl>
                        @if ($item['blurb'] ?? false)
                            <p class="mt-4 max-w-xl leading-relaxed text-white/80">{{ $item['blurb'] }}</p>
                        @endif
                        @if ($memorial)
                            <a href="{{ route('in-memoriam/'.$memorial['slug']) }}" class="btn-outline mt-5 text-white">In Memoriam page</a>
                        @endif
                    </div>
                </div>
                @if ($item['quote'] ?? false)
                    <blockquote class="border-l-2 border-accent pl-5">
                        <p class="font-display text-xl italic leading-relaxed text-white/90">&ldquo;{{ $item['quote'] }}&rdquo;</p>
                        <cite class="mt-3 block text-sm not-italic text-white/50">- {{ $item['name'] }}</cite>
                    </blockquote>
                @endif
            </div>
        </div>
    </section>

    @if (trim($item['body'] ?? '') !== '')
        <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <p class="kicker">About {{ $item['name'] }}</p>
            <div class="prose-site mt-4">{!! Content::renderMarkdown($item['body']) !!}</div>
        </section>
    @endif

    @if ($photos->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            <p class="kicker">Gallery</p>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" data-gallery>
                @foreach ($photos as $photo)
                    <button type="button" class="group block overflow-hidden bg-smoke text-left" data-gallery-item
                            data-full="{{ asset($photo['path']) }}"
                            data-caption="{{ trim(($photo['caption'] ?? '').' '.($photo['credit'] ?? '')) }}">
                        <img src="{{ asset($photo['path']) }}" alt="{{ $photo['caption'] ?? 'Photo by '.$item['name'] }}" loading="lazy"
                             class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                    </button>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-mist">Click a photo to open it. Photographs remain the property of their authors.</p>
        </section>
    @endif

    @if (count($stories))
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            <p class="kicker">Related stories</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($stories as $story)
                    @include('components.story-card', ['item' => $story])
                @endforeach
            </div>
        </section>
    @endif

    @include('components.cta-band', [
        'heading' => 'Preserving stories. Honoring humanity.',
        'text' => 'Your support helps us keep these powerful stories alive for the future.',
    ])
@endsection
