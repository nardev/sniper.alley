@extends('layouts.site')
@php
    use App\Content;
    $title = 'My Story / Mission';
    $story = Content::page('my-story');
    $mission = Content::page('mission');
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'My Story',
        'lede' => 'Why this archive exists, told by its founder.',
        'compact' => true,
        'image' => ($story['background'] ?? null) ? 'pages/my-story/'.$story['background'] : null,
    ])

    <article class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        @if ($story)
            <p class="kicker">WAR</p>
            <div class="prose-site mt-6">{!! Content::renderMarkdown($story['body']) !!}</div>
        @endif

        @if ($mission)
            <p class="kicker mt-16" id="mission">Mission</p>
            <div class="prose-site mt-6">{!! Content::renderMarkdown($mission['body']) !!}</div>
        @endif
    </article>

    @include('components.cta-band', [
        'heading' => 'Support the archive',
        'text' => 'Contribute photographs, share a story, or help fund the work of preserving this memory.',
    ])
@endsection
