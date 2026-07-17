@extends('layouts.site')
@php
    use App\Content;
    $title = 'Contact';
    $contact = Content::page('contact');
@endphp
@section('main')
    @include('components.hero', [
        'heading' => 'Contact Us',
        'lede' => 'Tell us your story, ask a question, or send us war photographs. The archive is open to all.',
        'compact' => true,
    ])

    <section class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        @if ($contact)
            <div class="prose-site">{!! Content::renderMarkdown($contact['body']) !!}</div>
        @endif
        <p class="mt-8 text-xs font-bold uppercase tracking-widest text-mist">Follow us</p>
        <div class="mt-3 flex gap-4">
            @include('components.social-links', ['class' => 'h-5 w-5 text-mist hover:text-accent'])
        </div>
    </section>
@endsection
