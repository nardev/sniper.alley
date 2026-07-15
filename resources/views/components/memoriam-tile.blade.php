@php
    use App\Content;
    $cover = Content::image('memoriam', $item['slug'], $item['cover'] ?? null);
@endphp
<a href="{{ route('in-memoriam/'.$item['slug']) }}" class="group flex items-center gap-4">
    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full bg-smoke">
        @if ($cover)
            <img src="{{ asset($cover) }}" alt="{{ $item['name'] }}" loading="lazy" class="h-full w-full object-cover grayscale">
        @endif
    </div>
    <div>
        <h3 class="font-display text-lg font-bold text-white group-hover:text-accent">{{ $item['name'] }}</h3>
        <p class="text-xs tracking-wide text-white/50">{{ $item['born'] ?? '' }} - {{ $item['died'] ?? '' }}</p>
        @if ($item['excerpt'] ?? false)
            <p class="mt-1 line-clamp-2 max-w-xs text-sm text-white/70">{{ $item['excerpt'] }}</p>
        @endif
    </div>
</a>
