@extends('layouts.site')
@php
    use App\Content;
    $featured = Content::featuredStory();
    $recentStories = collect(Content::stories())->reject(fn ($story) => $story['slug'] === ($featured['slug'] ?? null))->take(3);
    $featuredPhotographers = collect(Content::photographers())->filter(fn ($item) => $item['featured'] ?? false)->take(4);
    if ($featuredPhotographers->count() < 4) {
        $featuredPhotographers = $featuredPhotographers->union(
            collect(Content::photographers())->filter(fn ($item) => Content::photographerCover($item))->take(4)
        )->take(4);
    }
    $memorials = collect(Content::memoriam())->take(3);
    $latestPosts = collect(Content::latest())->take(4);
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Sniper Alley<br>Photo Archive',
        'lede' => 'Stories behind the photographs. Voices of the photographers. Memory of Sarajevo.',
        'actions' => ($featured ? '<a href="'.e(route('stories-behind-photo/'.$featured['slug'])).'" class="btn-primary">Watch Latest Story</a>' : '')
            .'<a href="'.e(route('photographers')).'" class="btn-outline text-white">Explore Photographers</a>',
    ])

    @if ($featured)
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
            <p class="kicker">Featured Story Behind the Photo</p>
            <div class="mt-5 grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    @include('components.story-card', ['item' => $featured])
                </div>
                <div class="divide-y divide-black/10">
                    @foreach ($recentStories as $story)
                        <a href="{{ route('stories-behind-photo/'.$story['slug']) }}" class="group flex gap-4 py-4 first:pt-0">
                            <div class="relative aspect-video w-32 shrink-0 overflow-hidden bg-smoke">
                                @php $thumb = Content::storyCover($story) @endphp
                                @if ($thumb)
                                    <img src="{{ asset($thumb) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @elseif (Content::storyThumbnail($story))
                                    <img src="{{ Content::storyThumbnail($story) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @endif
                            </div>
                            <div>
                                <h3 class="font-display text-base font-bold leading-snug group-hover:text-accent">{{ $story['title'] }}</h3>
                                <p class="mt-1 text-xs font-semibold text-accent">{{ Content::photographer($story['photographer'] ?? null)['name'] ?? '' }}</p>
                                @if ($story['date'] ?? false)
                                    <p class="mt-0.5 text-xs text-mist">{{ date('M j, Y', strtotime((string) $story['date'])) }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="bg-white/60">
        <div class="mx-auto grid max-w-7xl gap-5 px-4 py-14 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
            @foreach ([
                ['stories-behind-photo', 'Stories Behind Photo', 'Photographers speak about the moment, the image, and what stayed with them.', 'View stories'],
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
                <h2 class="mt-1 font-display text-2xl font-bold sm:text-3xl">Explore the people behind the images.</h2>
            </div>
            <a href="{{ route('photographers') }}" class="hidden shrink-0 text-xs font-bold uppercase tracking-widest text-accent hover:text-accent-deep sm:block">View all photographers &rarr;</a>
        </div>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($featuredPhotographers as $item)
                @include('components.photographer-card', ['item' => $item])
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
                <div class="mt-6 grid gap-8 md:grid-cols-3">
                    @foreach ($memorials as $item)
                        @include('components.memoriam-tile', ['item' => $item])
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
