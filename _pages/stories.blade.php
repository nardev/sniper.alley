@extends('layouts.site')
@php
    use App\Content;
    $title = 'Stories of Others';
    $intro = Content::page('stories');
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Stories of Others',
        'lede' => 'Written stories from survivors, families, and witnesses of the siege.',
        'compact' => true,
    ])

    <section class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        @if ($intro)
            <div class="prose-site">{!! Content::renderMarkdown($intro['body']) !!}</div>
        @endif
        <div class="mt-10 border-l-4 border-accent bg-white p-6 shadow-sm">
            <p class="font-display text-xl font-bold">Want your story published?</p>
            <p class="mt-2 text-sm text-ink/70">Write to us and share it with the world: <a class="font-semibold text-accent" href="mailto:info@sniperalley.photo">info@sniperalley.photo</a></p>
        </div>
    </section>
@endsection
