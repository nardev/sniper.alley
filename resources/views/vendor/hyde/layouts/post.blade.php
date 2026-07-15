@extends('layouts.site')
@php
    $categoryLabels = [
        'announcement' => 'Announcement',
        'media-mention' => 'Media Mention',
        'film' => 'Film',
        'memorial' => 'Memorial',
        'update' => 'Update',
    ];
    $category = (string) $page->matter('category', 'update');
    $image = $page->matter('image');
    $external = $page->matter('link');
@endphp
@section('main')
    <section class="bg-night text-white">
        <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <a href="{{ route('latest') }}" class="text-xs font-bold uppercase tracking-widest text-accent hover:text-white">&larr; Back to latest</a>
            <p class="kicker mt-6">{{ $categoryLabels[$category] ?? ucfirst($category) }}</p>
            <h1 class="mt-2 font-display text-4xl font-bold leading-tight">{{ $page->title }}</h1>
            @if ($page->date)
                <p class="mt-3 text-sm text-white/60">{{ $page->date->short }}</p>
            @endif
        </div>
    </section>

    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        @if ($image && file_exists(\Hyde\Hyde::path('_media/'.$image)))
            <img src="{{ asset($image) }}" alt="" class="mb-8 w-full object-cover">
        @endif
        <div class="prose-site">
            {{ $content }}
        </div>
        @if ($external)
            <p class="mt-8"><a href="{{ $external }}" target="_blank" rel="noopener" class="btn-primary">Read the original &rarr;</a></p>
        @endif
    </article>

    @include('components.cta-band')
@endsection
