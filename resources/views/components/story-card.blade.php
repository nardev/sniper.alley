@php
    use App\Content;
    $localCover = Content::storyCover($item);
    $remoteThumb = Content::storyThumbnail($item);
    $byline = Content::photographer($item['photographer'] ?? null)['name'] ?? ($item['photographer'] ?? null);
    $season = $item['season'] ?? null;
@endphp
<article class="group bg-white shadow-sm ring-1 ring-black/5 transition-shadow hover:shadow-lg">
    <a href="{{ route('stories-behind-the-photos/'.$item['slug']) }}" class="block">
        <div class="relative aspect-video overflow-hidden bg-smoke">
            @if ($localCover)
                <img src="{{ asset($localCover) }}" alt="{{ $item['title'] }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
            @elseif ($remoteThumb)
                <img src="{{ $remoteThumb }}" alt="{{ $item['title'] }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
            @endif
            <span class="absolute inset-0 flex items-center justify-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-black/60 text-white transition group-hover:bg-accent">
                    <svg class="ml-0.5 h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </span>
            @if ($season)
                <span class="absolute left-2 top-2 px-2 py-0.5 text-[11px] font-bold uppercase tracking-widest text-white {{ (int) $season === 2 ? 'bg-accent' : 'bg-black/70' }}">Season {{ $season }}</span>
            @endif
            @if ($item['duration'] ?? false)
                <span class="absolute bottom-2 right-2 bg-black/70 px-1.5 py-0.5 text-xs text-white">{{ $item['duration'] }}</span>
            @endif
        </div>
        <div class="p-4">
            <h3 class="font-display text-lg font-bold leading-snug group-hover:text-accent">{{ $item['title'] }}</h3>
            @if ($byline)
                <p class="mt-1 text-xs font-semibold text-accent">{{ $byline }}{{ $season ? ' | S'.$season.' E'.($item['episode'] ?? '') : '' }}</p>
            @endif
            @if ($item['date'] ?? false)
                <p class="mt-1 text-xs text-mist">{{ date('M j, Y', strtotime((string) $item['date'])) }}</p>
            @endif
            @if ($item['excerpt'] ?? false)
                <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink/70">{{ $item['excerpt'] }}</p>
            @endif
        </div>
    </a>
</article>
