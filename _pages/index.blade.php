@extends('layouts.site')
@php
    use App\Content;
    $featured = Content::featuredStory();
    // Pools are larger than what is shown: the page renders the first few and
    // ships the rest in inert templates, and app.js picks a random set per visit.
    $featuredPhotographers = collect(Content::photographers())
        ->filter(fn ($item) => Content::photographerCover($item))
        ->shuffle();
    $memorials = collect(Content::memoriam())->shuffle();
    $latestPosts = collect(Content::latest())->take(4);

    // Header background: photos defined in content/headers/photos.md (home),
    // else a random photo drawn from every gallery.
    $heroImage = Content::headerImage('home',
        collect(Content::photographers())->flatMap(fn ($p) => collect($p['photos'] ?? [])
            ->map(fn ($ph) => Content::image('photographers', $p['slug'], $ph['file'] ?? null))));
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Sniper Alley<br>Photo Archive',
        'lede' => 'Stories behind the photographs. Voices of the photographers. Memory of Sarajevo.',
        'image' => $heroImage,
        'actions' => ($featured ? '<a href="'.e(route('stories-behind-the-photos/'.$featured['slug'])).'" class="btn-primary">Watch Latest Story</a>' : '')
            .'<a href="'.e(route('photographers')).'" class="btn-outline text-white">Explore Photographers</a>',
    ])

    @if ($featured)
        @php
            $allStories = collect(Content::stories())
                ->sortBy([['season', 'asc'], ['episode', 'asc']])->values();
            $featuredPhotographer = Content::photographer($featured['photographer'] ?? null);
            $featuredThumb = ($fc = Content::storyCover($featured)) ? asset($fc) : Content::storyThumbnail($featured);
            $calendar = '<svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
        @endphp
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
            <p class="kicker">Featured Stories Behind the Photos</p>
            <div class="mt-5 grid gap-8 lg:grid-cols-3 lg:items-start">
                <div class="lg:col-span-2 lg:flex lg:items-start lg:gap-6">
                    <a href="{{ route('stories-behind-the-photos/'.$featured['slug']) }}" class="group relative block aspect-video w-full overflow-hidden bg-smoke lg:h-[19rem] lg:w-auto lg:shrink-0">
                        @if ($featuredThumb)
                            <img src="{{ $featuredThumb }}" alt="{{ $featured['title'] }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                        @endif
                        <span class="absolute inset-0 flex items-center justify-center">
                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-black/60 text-white transition group-hover:bg-accent">
                                <svg class="ml-1 h-7 w-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </span>
                        @if ($featured['duration'] ?? false)
                            <span class="absolute bottom-2 right-2 bg-black/70 px-2 py-1 text-xs text-white">{{ $featured['duration'] }}</span>
                        @endif
                    </a>
                    <div class="mt-6 lg:mt-0 lg:flex-1">
                        <p class="kicker">New story</p>
                        <h3 class="mt-2 font-display text-2xl font-bold leading-tight sm:text-3xl">{{ $featured['title'] }}</h3>
                        @if ($featuredPhotographer)
                            <a href="{{ route('photographers/'.$featuredPhotographer['slug']) }}" class="mt-2 inline-block font-semibold text-accent hover:text-accent-deep">{{ $featuredPhotographer['name'] }}</a>
                        @endif
                        @if ($featured['excerpt'] ?? false)
                            <p class="mt-3 leading-relaxed text-ink/70">{{ $featured['excerpt'] }}</p>
                        @endif
                        @if ($featured['date'] ?? false)
                            <p class="mt-3 flex items-center gap-1.5 text-xs text-mist">{!! $calendar !!}{{ date('M j, Y', strtotime((string) $featured['date'])) }}</p>
                        @endif
                        <a href="{{ route('stories-behind-the-photos/'.$featured['slug']) }}" class="btn-outline mt-5">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            Watch the story
                        </a>
                    </div>
                </div>

                <div class="relative" data-vslider-root>
                    <button type="button" data-vslider-up aria-label="Previous stories" class="absolute left-1/2 top-0 z-10 hidden -translate-x-1/2 -translate-y-1/2 rounded-full bg-night p-1.5 text-white shadow-lg transition-colors hover:bg-accent">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m18 15-6-6-6 6"/></svg>
                    </button>
                    <div data-vslider class="slider-scroll h-[19rem] divide-y divide-black/10 overflow-y-auto">
                        @foreach ($allStories as $story)
                            @php
                                $rowThumb = ($rc = Content::storyCover($story)) ? asset($rc) : Content::storyThumbnail($story);
                                $isActive = $story['slug'] === $featured['slug'];
                            @endphp
                            <a href="{{ route('stories-behind-the-photos/'.$story['slug']) }}" class="group flex gap-4 py-4 first:pt-0 {{ $isActive ? 'bg-black/[0.04]' : '' }}">
                                <div class="relative aspect-video w-32 shrink-0 overflow-hidden bg-smoke">
                                    @if ($rowThumb)
                                        <img src="{{ $rowThumb }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @endif
                                    <span class="absolute inset-0 flex items-center justify-center">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white transition group-hover:bg-accent">
                                            <svg class="ml-0.5 h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                        </span>
                                    </span>
                                    @if ($story['duration'] ?? false)
                                        <span class="absolute bottom-1 right-1 bg-black/70 px-1 py-0.5 text-[10px] text-white">{{ $story['duration'] }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-display text-base font-bold leading-snug {{ $isActive ? 'text-accent' : 'group-hover:text-accent' }}">{{ $story['title'] }}</h3>
                                    <p class="mt-1 text-xs font-semibold text-accent">{{ Content::photographer($story['photographer'] ?? null)['name'] ?? '' }}{{ ($story['season'] ?? false) ? ' | S'.$story['season'].' E'.($story['episode'] ?? '') : '' }}</p>
                                    @if ($story['date'] ?? false)
                                        <p class="mt-0.5 flex items-center gap-1.5 text-xs text-mist">{!! $calendar !!}{{ date('M j, Y', strtotime((string) $story['date'])) }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <button type="button" data-vslider-down aria-label="More stories" class="absolute bottom-0 left-1/2 z-10 hidden -translate-x-1/2 translate-y-1/2 rounded-full bg-night p-1.5 text-white shadow-lg transition-colors hover:bg-accent">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                </div>
            </div>
        </section>
    @endif

    <section class="bg-white/60">
        <div class="mx-auto grid max-w-7xl gap-5 px-4 py-14 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
            @foreach ([
                ['stories-behind-the-photos', 'Stories Behind the Photos', 'Photographers speak about the moment, the image, and what stayed with them.', 'View stories'],
                ['photographers', 'Photographers', 'Discover the photographers and explore their stories and photo galleries.', 'Explore'],
                ['in-memoriam', 'In Memoriam', 'Remembering photographers whose work and witness remain part of Sarajevo\'s memory.', 'Visit memoriam'],
                ['latest', 'Latest', 'Updates, announcements, media mentions and more from the archive.', 'See latest'],
            ] as [$key, $heading, $text, $cta])
                <a href="{{ route($key) }}" class="group bg-night p-6 text-white transition-colors hover:bg-coal">
                    <h2 class="font-display text-xl font-bold">{{ $heading }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-white/60">{{ $text }}</p>
                    <p class="mt-4 text-xs font-bold uppercase tracking-widest text-accent group-hover:text-white">{{ $cta }} &rarr;</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="kicker">Photographers</p>
                <h2 class="mt-1 font-display text-2xl font-bold sm:text-3xl">Explore more photo galleries from the archive.</h2>
            </div>
            <a href="{{ route('photographers') }}" class="hidden shrink-0 text-xs font-bold uppercase tracking-widest text-accent hover:text-accent-deep sm:block">View all photographers &rarr;</a>
        </div>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-random-pool="4">
            @foreach ($featuredPhotographers as $item)
                @if ($loop->index < 4)
                    @include('components.photographer-card', ['item' => $item])
                @else
                    <template>@include('components.photographer-card', ['item' => $item])</template>
                @endif
            @endforeach
        </div>
        <a href="{{ route('photographers') }}" class="mt-6 block text-center text-xs font-bold uppercase tracking-widest text-accent sm:hidden">View all photographers &rarr;</a>
    </section>

    @if ($memorials->isNotEmpty())
        <section class="bg-night">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <div class="flex items-center justify-between">
                    <p class="kicker">In Memoriam</p>
                    <a href="{{ route('in-memoriam') }}" class="text-xs font-bold uppercase tracking-widest text-accent hover:text-white">Visit In Memoriam &rarr;</a>
                </div>
                <div class="mt-6 grid gap-8 md:grid-cols-3" data-random-pool="3">
                    @foreach ($memorials as $item)
                        @if ($loop->index < 3)
                            @include('components.memoriam-tile', ['item' => $item])
                        @else
                            <template>@include('components.memoriam-tile', ['item' => $item])</template>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($latestPosts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
            <div class="flex items-end justify-between gap-4">
                <p class="kicker">Latest</p>
                <a href="{{ route('latest') }}" class="text-xs font-bold uppercase tracking-widest text-accent hover:text-accent-deep">View all latest &rarr;</a>
            </div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latestPosts as $post)
                    @include('components.latest-card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif

    @include('components.cta-band')
@endsection
