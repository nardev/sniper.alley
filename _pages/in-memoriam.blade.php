@extends('layouts.site')
@php
    use App\Content;
    $title = 'In Memoriam';
    $memorials = collect(Content::memoriam());

    // Header background: photos defined in content/headers/photos.md (memoriam),
    // else a random tribute portrait or banner.
    $heroImage = Content::headerImage('memoriam',
        $memorials->map(fn ($m) => Content::memorialTileImage($m)));
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'In Memoriam',
        'lede' => 'Remembering the photographers and journalists who bore witness to the siege and are no longer with us. Their work remains part of our collective memory.',
        'compact' => true,
        'image' => $heroImage,
    ])

    <section class="bg-night text-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
            @if ($memorials->isEmpty())
                <p class="py-16 text-center text-white/60">Memorial pages are being prepared.</p>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($memorials as $item)
                        @php $cover = Content::memorialTileImage($item) @endphp
                        <a href="{{ route('in-memoriam/'.$item['slug']) }}" class="group bg-coal ring-1 ring-white/10 transition hover:ring-accent">
                            <div class="aspect-[3/2] overflow-hidden">
                                @if ($cover)
                                    <img src="{{ asset($cover) }}" alt="{{ $item['name'] }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                                @else
                                    <div class="flex h-full w-full items-center justify-center font-display text-5xl text-white/20">{{ mb_substr($item['name'] ?? '?', 0, 1) }}</div>
                                @endif
                            </div>
                            <div class="p-5">
                                <h2 class="font-display text-xl font-bold group-hover:text-accent">{{ $item['name'] }}</h2>
                                <p class="mt-1 text-xs tracking-widest text-white/50">{{ $item['born'] ?? '' }} - {{ $item['died'] ?? '' }}</p>
                                @if ($item['excerpt'] ?? false)
                                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-white/70">{{ $item['excerpt'] }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @include('components.cta-band', [
        'heading' => 'Preserving stories. Honoring humanity.',
        'text' => 'Your support helps us keep these powerful stories alive for the future.',
    ])
@endsection
