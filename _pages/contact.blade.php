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

    <section class="mx-auto grid max-w-5xl gap-12 px-4 py-14 sm:px-6 lg:grid-cols-2">
        <div>
            @if ($contact)
                <div class="prose-site">{!! Content::renderMarkdown($contact['body']) !!}</div>
            @endif
            <p class="mt-8 text-xs font-bold uppercase tracking-widest text-mist">Follow us</p>
            <div class="mt-3 flex gap-4">
                @include('components.social-links', ['class' => 'h-5 w-5 text-mist hover:text-accent'])
            </div>
        </div>
        <form action="mailto:info@sniperalley.photo" method="get" class="space-y-4" data-contact-form>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-mist" for="cf-name">Your name (required)</label>
                <input id="cf-name" name="name" type="text" required class="w-full border border-black/15 bg-white px-3 py-2.5 text-sm focus:border-accent focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-mist" for="cf-email">Your email (required)</label>
                <input id="cf-email" name="email" type="email" required class="w-full border border-black/15 bg-white px-3 py-2.5 text-sm focus:border-accent focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-mist" for="cf-phone">Your telephone number</label>
                <input id="cf-phone" name="phone" type="tel" class="w-full border border-black/15 bg-white px-3 py-2.5 text-sm focus:border-accent focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold uppercase tracking-widest text-mist" for="cf-message">Your message</label>
                <textarea id="cf-message" name="body" rows="6" class="w-full border border-black/15 bg-white px-3 py-2.5 text-sm focus:border-accent focus:outline-none"></textarea>
            </div>
            <button type="submit" class="btn-primary">Send message</button>
            <p class="text-xs text-mist">The form opens your email app. You can also write directly to <a class="text-accent" href="mailto:info@sniperalley.photo">info@sniperalley.photo</a>.</p>
        </form>
    </section>
@endsection
