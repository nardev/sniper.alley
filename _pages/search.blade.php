@extends('layouts.site')
@php
    $title = 'Search';
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Search',
        'lede' => 'Search photographers, galleries, stories, memorials, and news.',
        'compact' => true,
    ])

    <section class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        <div class="relative">
            <input type="search" data-search-input placeholder="Type to search..." autofocus
                   class="w-full border border-black/15 bg-white py-3.5 pl-12 pr-4 text-lg focus:border-accent focus:outline-none">
            <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-mist" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        </div>
        <p class="mt-3 text-sm text-mist" data-search-status>Start typing to search the archive.</p>
        <div class="mt-6 divide-y divide-black/10" data-search-results></div>
    </section>
@endsection
