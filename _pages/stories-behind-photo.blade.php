@extends('layouts.site')
@php
    use App\Content;
    $title = 'Stories Behind Photo';
    $stories = collect(Content::stories());
    $featured = Content::featuredStory();
    $seasons = $stories->groupBy(fn ($item) => (int) ($item['season'] ?? 0))->sortKeysDesc();

    // Header background: photos defined in content/headers/photos.md (stories),
    // else a random photo from the story photographers' galleries.
    $heroImage = Content::headerImage('stories',
        $stories->pluck('photographer')->filter()->unique()
            ->flatMap(fn ($slug) => collect(Content::photographer($slug)['photos'] ?? [])
                ->map(fn ($ph) => Content::image('photographers', $slug, $ph['file'] ?? null))));
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Stories Behind Photo',
        'lede' => 'Every photograph holds a story beyond the frame. Photographers speak about the moments, the risks, and the human truths behind the images that shaped our memory of Sarajevo and the siege.',
        'compact' => true,
        'image' => $heroImage,
    ])

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        @if ($stories->isEmpty())
            <p class="py-16 text-center text-mist">Stories are being prepared. Check back soon.</p>
        @else
            @if ($featured)
                @php
                    $featuredPhotographer = Content::photographer($featured['photographer'] ?? null);
                    $featuredThumb = ($fc = Content::storyCover($featured)) ? asset($fc) : Content::storyThumbnail($featured);
                @endphp
                <div class="border-l-4 border-accent bg-white p-6 shadow-lg ring-1 ring-black/5 sm:p-8">
                    <div class="grid items-center gap-8 lg:grid-cols-2">
                    <a href="{{ route('stories-behind-photo/'.$featured['slug']) }}" class="group relative block aspect-video overflow-hidden bg-smoke">
                        @if ($featuredThumb)
                            <img src="{{ $featuredThumb }}" alt="{{ $featured['title'] }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                        @endif
                        <span class="absolute inset-0 flex items-center justify-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-black/60 text-white transition group-hover:bg-accent">
                                <svg class="ml-1 h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </span>
                        @if ($featured['duration'] ?? false)
                            <span class="absolute bottom-2 right-2 bg-black/70 px-2 py-1 text-xs text-white">{{ $featured['duration'] }}</span>
                        @endif
                    </a>
                    <div>
                        <p class="kicker">Featured story</p>
                        <h2 class="mt-2 font-display text-3xl font-bold">{{ $featured['title'] }}</h2>
                        @if ($featuredPhotographer)
                            <a href="{{ route('photographers/'.$featuredPhotographer['slug']) }}" class="mt-2 inline-block font-semibold text-accent hover:text-accent-deep">{{ $featuredPhotographer['name'] }}</a>
                        @endif
                        <p class="mt-2 text-sm font-semibold text-accent">Season {{ $featured['season'] ?? '' }}, episode {{ $featured['episode'] ?? '' }}</p>
                        @if ($featured['date'] ?? false)
                            <p class="mt-1 text-xs text-mist">{{ date('F j, Y', strtotime((string) $featured['date'])) }}</p>
                        @endif
                        @if ($featured['excerpt'] ?? false)
                            <p class="mt-3 leading-relaxed text-ink/80">{{ $featured['excerpt'] }}</p>
                        @endif
                        <a href="{{ route('stories-behind-photo/'.$featured['slug']) }}" class="btn-primary mt-6">Watch the story</a>
                    </div>
                    </div>
                </div>
            @endif

            <div data-filter-grid>
                @foreach ($seasons as $seasonNumber => $seasonStories)
                    <div class="mt-12 flex items-center gap-4" data-season-header>
                        <h2 class="font-display text-2xl font-bold">
                            {{ $seasonNumber ? 'Season '.$seasonNumber : 'Stories' }}
                        </h2>
                        <span class="h-1 w-10 {{ (int) $seasonNumber === 2 ? 'bg-accent' : 'bg-ink/30' }}"></span>
                        <span class="text-sm text-mist">{{ $seasonStories->count() }} episodes</span>
                    </div>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-season-grid>
                        @foreach ($seasonStories->sortBy(fn ($item) => (int) ($item['episode'] ?? 0)) as $item)
                            <div data-photographer="{{ $item['photographer'] ?? '' }}">
                                @include('components.story-card', ['item' => $item])
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @include('components.cta-band', [
        'heading' => 'Why this archive matters',
        'text' => 'These stories are more than images. They are witness, memory, and a promise never to forget.',
    ])
@endsection
