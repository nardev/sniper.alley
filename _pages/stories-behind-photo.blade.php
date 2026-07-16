@extends('layouts.site')
@php
    use App\Content;
    $title = 'Stories Behind Photo';
    $stories = collect(Content::stories());
    $featured = Content::featuredStory();
    $seasons = $stories->groupBy(fn ($item) => (int) ($item['season'] ?? 0))->sortKeysDesc();
    $photographerOptions = $stories->pluck('photographer')->filter()->unique()
        ->map(fn ($slug) => ['slug' => $slug, 'name' => Content::photographer($slug)['name'] ?? $slug])
        ->sortBy('name');
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Stories Behind Photo',
        'lede' => 'Every photograph holds a story beyond the frame. Photographers speak about the moments, the risks, and the human truths behind the images that shaped our memory of Sarajevo and the siege.',
        'compact' => true,
    ])

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        @if ($stories->isEmpty())
            <p class="py-16 text-center text-mist">Stories are being prepared. Check back soon.</p>
        @else
            <div class="flex flex-wrap items-center gap-3" data-filterbar>
                <label class="text-xs font-bold uppercase tracking-widest text-mist">Filter stories</label>
                <select data-filter-select="photographer" class="border border-black/15 bg-white px-3 py-2 text-sm">
                    <option value="">All photographers</option>
                    @foreach ($photographerOptions as $option)
                        <option value="{{ $option['slug'] }}">{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </div>

            @if ($featured)
                <div class="mt-8 grid items-center gap-8 bg-white p-6 shadow-sm ring-1 ring-black/5 lg:grid-cols-2">
                    <div>
                        @include('components.story-card', ['item' => $featured])
                    </div>
                    <div>
                        <p class="kicker">Featured story</p>
                        <h2 class="mt-2 font-display text-3xl font-bold">{{ $featured['title'] }}</h2>
                        <p class="mt-2 text-sm font-semibold text-accent">
                            Season {{ $featured['season'] ?? '' }}, episode {{ $featured['episode'] ?? '' }}
                        </p>
                        @if ($featured['excerpt'] ?? false)
                            <p class="mt-3 leading-relaxed text-ink/80">{{ $featured['excerpt'] }}</p>
                        @endif
                        <a href="{{ route('stories-behind-photo/'.$featured['slug']) }}" class="btn-primary mt-6">Watch the story</a>
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
