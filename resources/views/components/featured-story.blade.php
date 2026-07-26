@php
    $thumb = ($fc = \App\Content::storyCover($story)) ? asset($fc) : \App\Content::storyThumbnail($story);
@endphp
<div class="lg:flex lg:items-start lg:gap-6" data-story-slug="{{ $story['slug'] }}">
    <a href="{{ route('stories-behind-the-photos/'.$story['slug']) }}" class="group relative block aspect-video w-full overflow-hidden bg-smoke lg:h-[19rem] lg:w-auto lg:shrink-0">
        @if ($thumb)
            <img src="{{ $thumb }}" alt="{{ $story['title'] }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @endif
        <span class="absolute inset-0 flex items-center justify-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-black/60 text-white transition group-hover:bg-accent">
                <svg class="ml-1 h-7 w-7" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            </span>
        </span>
        @if ($story['duration'] ?? false)
            <span class="absolute bottom-2 right-2 bg-black/70 px-2 py-1 text-xs text-white">{{ $story['duration'] }}</span>
        @endif
    </a>
    <div class="mt-6 lg:mt-0 lg:flex-1">
        <p class="kicker">Photographer&rsquo;s Story</p>
        <h3 class="mt-2 font-display text-2xl font-bold leading-tight sm:text-3xl">{{ $story['title'] }}</h3>
        @if ($story['season'] ?? false)
            <p class="mt-2 font-semibold uppercase text-accent">Season {{ $story['season'] }} | Episode {{ $story['episode'] ?? '' }}</p>
        @endif
        @if ($story['excerpt'] ?? false)
            <p class="mt-3 leading-relaxed text-ink/70">{{ $story['excerpt'] }}</p>
        @endif
        @if ($story['date'] ?? false)
            <p class="mt-3 flex items-center gap-1.5 text-xs text-mist">{!! $calendar !!}{{ date('M j, Y', strtotime((string) $story['date'])) }}</p>
        @endif
        <a href="{{ route('stories-behind-the-photos/'.$story['slug']) }}" class="btn-outline mt-5">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            Watch the story
        </a>
    </div>
</div>
