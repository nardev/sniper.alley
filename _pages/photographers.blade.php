@extends('layouts.site')
@php
    use App\Content;
    $title = 'Photographers';
    $photographers = collect(Content::photographers());
    $totalPhotos = $photographers->sum(fn ($p) => count($p['photos'] ?? []));

    // Header background: photos defined in content/headers/photos.md (photographers),
    // else a random photo drawn from every gallery.
    $heroImage = Content::headerImage('photographers',
        $photographers->flatMap(fn ($p) => collect($p['photos'] ?? [])
            ->map(fn ($ph) => Content::image('photographers', $p['slug'], $ph['file'] ?? null))));
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Photographers',
        'lede' => 'Explore the photographers whose courage, vision, and compassion captured the truth of war and resilience in Sarajevo and beyond.',
        'compact' => true,
        'image' => $heroImage,
    ])

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <div class="flex flex-wrap items-center gap-3">
            <select data-sort-select aria-label="Sort" class="border border-black/15 bg-white px-3 py-2.5 text-sm text-ink focus:border-accent focus:outline-none">
                <option value="name-asc">Name (A to Z)</option>
                <option value="name-desc">Name (Z to A)</option>
                <option value="photos-desc">Most photos</option>
                <option value="photos-asc">Fewest photos</option>
            </select>
            <div class="relative grow sm:max-w-sm">
                <input type="search" data-filter-input placeholder="Search photographers..."
                       class="w-full border border-black/15 bg-white py-2.5 pl-10 pr-3 text-sm focus:border-accent focus:outline-none">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-mist" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-mist">
                <input type="checkbox" data-filter-memoriam-toggle class="accent-accent">
                In memoriam only
            </label>
            <p class="ml-auto text-sm text-mist"><span data-filter-count>{{ $photographers->count() }}</span> photographers &middot; {{ number_format($totalPhotos) }} photos</p>
        </div>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-filter-grid>
            @foreach ($photographers as $item)
                @include('components.photographer-card', ['item' => $item])
            @endforeach
        </div>
        <p class="hidden py-16 text-center text-mist" data-filter-empty>No photographers match your search.</p>
    </section>

    @include('components.cta-band')
@endsection
