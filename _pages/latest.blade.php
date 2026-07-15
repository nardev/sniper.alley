@extends('layouts.site')
@php
    use App\Content;
    $title = 'Latest';
    $posts = collect(Content::latest());
    $categories = [
        '' => 'All',
        'announcement' => 'Announcements',
        'media-mention' => 'Media Mentions',
        'film' => 'Films',
        'memorial' => 'Memorials',
        'update' => 'Updates',
    ];
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Latest',
        'lede' => 'Updates, stories, and news from the archive. Honoring the past. Sharing it forward.',
        'compact' => true,
    ])

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <div class="flex flex-wrap items-center gap-1 border-b border-black/10 pb-px" data-tabs>
            @foreach ($categories as $value => $label)
                <button type="button" data-tab="{{ $value }}"
                        class="border-b-2 px-4 py-2.5 text-xs font-bold uppercase tracking-widest transition-colors {{ $value === '' ? 'border-accent text-accent' : 'border-transparent text-mist hover:text-ink' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($posts->isEmpty())
            <p class="py-16 text-center text-mist">No posts yet. Check back soon.</p>
        @else
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-filter-grid>
                @foreach ($posts as $post)
                    @include('components.latest-card', ['post' => $post])
                @endforeach
            </div>
            <p class="hidden py-16 text-center text-mist" data-filter-empty>No posts in this category yet.</p>
        @endif
    </section>

    @include('components.cta-band', [
        'heading' => 'Help preserve these stories',
        'text' => 'Your support helps us protect the archive and share the voices that history must never forget.',
    ])
@endsection
