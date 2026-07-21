@extends('layouts.site')
@php
    use App\Content;
    $title = 'Donate';
    $donate = Content::page('donate');
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Support the Archive',
        'lede' => 'Contribute photographs, share a story or help fund the work of preserving our memories.',
        'compact' => true,
    ])

    <section class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        @if ($donate)
            <div class="prose-site">{!! Content::renderMarkdown($donate['body']) !!}</div>
        @else
            <p class="text-mist">Donation options are being prepared. In the meantime, contact us at <a class="text-accent" href="mailto:info@sniperalley.photo">info@sniperalley.photo</a>.</p>
        @endif
    </section>
@endsection
