@extends('layouts.site')
@php
    use App\Content;
    $slug = $item['slug'];
    $photographer = Content::photographer($item['photographer'] ?? null);
    $related = collect(Content::stories())->reject(fn ($story) => $story['slug'] === $slug)->take(3);
    $relatedPhotos = $photographer
        ? collect($photographer['photos'] ?? [])
            ->map(fn ($photo) => $photo + ['path' => Content::image('photographers', $photographer['slug'], $photo['file'] ?? null)])
            ->filter(fn ($photo) => $photo['path'])->take(4)
        : collect();
@endphp
@section('main')
    <section class="bg-night text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            <a href="{{ route('stories-behind-photo') }}" class="text-xs font-bold uppercase tracking-widest text-accent hover:text-white">&larr; Back to stories</a>
            <div class="mt-6 grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <p class="kicker">Story Behind the Photo</p>
                    <h1 class="mt-3 font-display text-4xl font-bold leading-tight sm:text-5xl">{{ $item['title'] }}</h1>
                    @if ($photographer)
                        <a href="{{ route('photographers/'.$photographer['slug']) }}" class="mt-3 inline-block font-semibold text-accent hover:text-white">{{ $photographer['name'] }}</a>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-white/60">
                        @if ($item['location'] ?? false)
                            <span>{{ $item['location'] }}</span>
                        @endif
                        @if ($item['date'] ?? false)
                            <span>{{ date('F j, Y', strtotime((string) $item['date'])) }}</span>
                        @endif
                    </div>
                    @if ($item['excerpt'] ?? false)
                        <p class="mt-5 max-w-lg leading-relaxed text-white/80">{{ $item['excerpt'] }}</p>
                    @endif
                </div>
                @if ($item['youtube'] ?? false)
                    <div class="aspect-video w-full bg-black">
                        <iframe class="h-full w-full" src="https://www.youtube-nocookie.com/embed/{{ $item['youtube'] }}"
                                title="{{ $item['title'] }}" loading="lazy" allowfullscreen
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl gap-12 px-4 py-12 sm:px-6 lg:grid lg:grid-cols-3">
        <article class="lg:col-span-2">
            @if (trim($item['body'] ?? '') !== '')
                <p class="kicker">The story</p>
                <div class="prose-site mt-4">{!! Content::renderMarkdown($item['body']) !!}</div>
            @endif
        </article>
        @if ($relatedPhotos->isNotEmpty())
            <aside class="mt-12 lg:mt-0">
                <p class="kicker">Related photographs</p>
                <div class="mt-4 space-y-4">
                    @foreach ($relatedPhotos as $photo)
                        <figure>
                            <img src="{{ asset($photo['path']) }}" alt="{{ $photo['caption'] ?? '' }}" loading="lazy" class="w-full object-cover">
                            @if ($photo['caption'] ?? false)
                                <figcaption class="mt-1.5 text-xs text-mist">{{ $photo['caption'] }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
                @if ($photographer)
                    <a href="{{ route('photographers/'.$photographer['slug']) }}" class="btn-outline mt-6 w-full justify-center">View all photos</a>
                @endif
            </aside>
        @endif
    </section>

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
