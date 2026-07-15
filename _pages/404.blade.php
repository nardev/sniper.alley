@extends('layouts.site')
@php
    $title = 'Page not found';
@endphp
@section('main')
    <section class="bg-night text-white">
        <div class="mx-auto flex max-w-7xl flex-col items-start px-4 py-28 sm:px-6">
            <p class="kicker">404</p>
            <h1 class="mt-3 font-display text-4xl font-bold sm:text-5xl">Page not found</h1>
            <p class="mt-4 max-w-md text-white/70">The page you are looking for does not exist or has moved. Try the search, or start from the front page.</p>
            <div class="mt-8 flex gap-3">
                <a href="{{ route('index') }}" class="btn-primary">Front page</a>
                <a href="{{ route('search') }}" class="btn-outline text-white">Search</a>
            </div>
        </div>
    </section>
@endsection
