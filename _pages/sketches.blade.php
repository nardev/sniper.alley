@extends('layouts.site')
@php
    use App\Content;
    $title = 'Sketches';
    $sketches = Content::page('sketches');
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Sketches',
        'lede' => 'Short written vignettes and memories from the founder.',
        'compact' => true,
    ])

    <section class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        @if ($sketches)
            <div class="prose-site">{!! Content::renderMarkdown($sketches['body']) !!}</div>
        @else
            <p class="text-mist">Sketches are being prepared.</p>
        @endif
    </section>
@endsection
