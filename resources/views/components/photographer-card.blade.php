@php
    use App\Content;
    $cover = Content::photographerCover($item);
    $thumbs = collect($item['photos'] ?? [])->skip($cover && empty($item['portrait']) ? 1 : 0)
        ->map(fn ($photo) => Content::image('photographers', $item['slug'], $photo['file'] ?? null))
        ->filter()->take(3);
    $dates = ($item['born'] ?? null) ? ($item['born'].' - '.($item['died'] ?? '')) : null;
@endphp
<a href="{{ route('photographers/'.$item['slug']) }}"
   class="group block bg-white shadow-sm ring-1 ring-black/5 transition-shadow hover:shadow-lg"
   data-filter-name="{{ mb_strtolower($item['name'] ?? $item['slug']) }}"
   data-filter-text="{{ mb_strtolower(trim(($item['country'] ?? '').' '.($item['role'] ?? '').' '.implode(' ', (array) ($item['tags'] ?? [])))) }}"
   data-filter-memoriam="{{ ($item['died'] ?? null) ? '1' : '0' }}">
    <div class="aspect-[4/3] overflow-hidden bg-smoke">
        @if ($cover)
            <img src="{{ asset($cover) }}" alt="{{ $item['name'] ?? $item['slug'] }}" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
        @else
            <div class="flex h-full w-full items-center justify-center text-4xl font-display text-white/30">{{ mb_substr($item['name'] ?? '?', 0, 1) }}</div>
        @endif
    </div>
    @if ($thumbs->isNotEmpty())
        <div class="grid grid-cols-3 gap-px bg-black/10">
            @foreach ($thumbs as $thumb)
                <img src="{{ asset($thumb) }}" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover">
            @endforeach
        </div>
    @endif
    <div class="p-4">
        <h3 class="font-display text-lg font-bold leading-snug">{{ $item['name'] ?? $item['slug'] }}</h3>
        @if ($dates)
            <p class="mt-0.5 text-xs tracking-wide text-mist">{{ trim($dates, ' -') }}</p>
        @endif
        @if ($item['blurb'] ?? false)
            <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink/70">{{ $item['blurb'] }}</p>
        @endif
        <p class="mt-3 text-xs text-mist">{{ count($item['photos'] ?? []) }} photos in gallery</p>
    </div>
</a>
