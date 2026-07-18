@extends('layouts.site')
@php
    use App\Content;
    $slug = $item['slug'];
    $photographer = Content::photographer($item['photographer'] ?? null);
    $galleryUrl = $photographer ? route('photographers/'.$photographer['slug']) : null;
    $galleryPhotos = $photographer
        ? collect($photographer['photos'] ?? [])
            ->map(fn ($photo) => $photo + ['path' => Content::image('photographers', $photographer['slug'], $photo['file'] ?? null)])
            ->filter(fn ($photo) => $photo['path'])->values()
        : collect();
    $headerPhoto = $galleryPhotos->first()['path'] ?? null;
    $relatedPhotos = $galleryPhotos->take(8);
    $related = collect(Content::stories())->reject(fn ($story) => $story['slug'] === $slug)->shuffle()->take(3);
@endphp
@section('main')
    <section class="relative overflow-hidden bg-night text-white">
        @if ($headerPhoto)
            <img src="{{ asset($headerPhoto) }}" alt="" class="absolute inset-0 h-full w-full object-cover grayscale" loading="eager">
            <div class="absolute inset-0 bg-gradient-to-t from-night via-night/80 to-night/50"></div>
        @endif
        <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 {{ $headerPhoto ? 'pt-24 lg:pt-40' : '' }}">
            <div class="max-w-3xl">
                <p class="kicker">The Story Behind the Photo</p>
                <h1 class="mt-3 font-display text-4xl font-bold leading-tight sm:text-5xl">{{ $item['title'] }}</h1>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm text-white/60">
                    @if ($item['season'] ?? false)
                        <span>Season {{ $item['season'] }}{{ ($item['episode'] ?? false) ? ', Episode '.$item['episode'] : '' }}</span>
                    @endif
                    @if ($item['date'] ?? false)
                        <span>{{ date('F j, Y', strtotime((string) $item['date'])) }}</span>
                    @endif
                </div>
                <a href="{{ route('stories-behind-the-photos') }}" class="btn-primary mt-8">&larr; Back to all stories</a>
            </div>
        </div>
    </section>

    @if ($item['youtube'] ?? false)
        <section class="mx-auto max-w-5xl px-4 pt-12 sm:px-6">
            <div class="aspect-video w-full bg-black">
                <iframe class="h-full w-full" src="https://www.youtube-nocookie.com/embed/{{ $item['youtube'] }}"
                        title="{{ $item['title'] }}" loading="lazy" allowfullscreen
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
        </section>
    @endif

    @if (trim($item['body'] ?? '') !== '')
        <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <p class="kicker">The story</p>
            <div class="prose-site mt-4">{!! Content::renderMarkdown($item['body']) !!}</div>
        </section>
    @endif

    @if ($relatedPhotos->isNotEmpty())
        <section class="bg-night text-white">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <div class="flex items-end justify-between gap-4">
                    <p class="kicker">{{ $photographer['name'] }}'s Photos</p>
                    @if ($galleryUrl)
                        <a href="{{ $galleryUrl }}" class="text-xs font-bold uppercase tracking-widest text-accent hover:text-white">View all photos &rarr;</a>
                    @endif
                </div>
                <div class="marquee mt-5">
                    <div class="marquee-track">
                        @foreach ($relatedPhotos->concat($relatedPhotos) as $i => $photo)
                            <a href="{{ $galleryUrl }}" class="group block w-64 shrink-0 me-4 sm:w-72" @if ($i >= $relatedPhotos->count()) aria-hidden="true" tabindex="-1" @endif>
                                <div class="overflow-hidden bg-smoke">
                                    <img src="{{ asset($photo['path']) }}" alt="{{ $photo['caption'] ?? 'Photograph by '.($photographer['name'] ?? '') }}" loading="lazy"
                                         class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                                </div>
                                @if ($photo['caption'] ?? false)
                                    <p class="mt-1.5 line-clamp-2 text-xs text-white/50">{{ $photo['caption'] }}</p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            <p class="kicker">Related stories</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $story)
                    @include('components.story-card', ['item' => $story])
                @endforeach
            </div>
        </section>
    @endif

    @include('components.cta-band')
@endsection
