@php
    $heroImage = $image ?? 'site/hero.jpg';
    $hasImage = file_exists(\Hyde\Hyde::path('_media/'.$heroImage));
@endphp
<section class="relative overflow-hidden bg-night text-white">
    @if ($hasImage)
        <img src="{{ asset($heroImage) }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-40" loading="eager">
        <div class="absolute inset-0 bg-gradient-to-r from-night/90 via-night/60 to-night/30"></div>
    @endif
    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 {{ ($compact ?? false) ? 'lg:py-20' : 'lg:py-28' }}">
        <h1 class="max-w-3xl font-display text-4xl font-bold leading-tight sm:text-5xl {{ ($compact ?? false) ? '' : 'lg:text-6xl' }}">{!! $heading !!}</h1>
        @isset ($lede)
            <p class="mt-5 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg">{{ $lede }}</p>
        @endisset
        @isset ($actions)
            <div class="mt-8 flex flex-wrap gap-3">{!! $actions !!}</div>
        @endisset
    </div>
</section>
