<!DOCTYPE html>
<html lang="{{ config('hyde.language', 'en') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) && $title ? $title.' | Sniper Alley' : 'Sniper Alley Photo Archive' }}</title>
    <meta name="description" content="@yield('description', 'Sniper Alley Photo Archive preserves the photographs, the photographers, and the visual memory of Sarajevo under siege, 1992-1996.')">
    <link rel="stylesheet" href="{{ asset('app.css') }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('site/favicon.jpg') }}">
</head>
<body class="bg-paper font-body text-ink antialiased" data-root="{{ str_repeat('../', substr_count(($page ?? null)?->getRouteKey() ?? '', '/')) }}">
    @include('components.header')
    <main id="content">
        @yield('main')
    </main>
    @include('components.footer')
    <div class="lightbox" id="lightbox" aria-hidden="true">
        <button type="button" class="absolute top-5 right-6 text-4xl leading-none text-white/70 hover:text-white" data-lightbox-close aria-label="Close">&times;</button>
        <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 p-3 text-3xl text-white/70 hover:text-white md:left-8" data-lightbox-prev aria-label="Previous">&#8592;</button>
        <figure class="text-center">
            <img src="" alt="">
            <figcaption class="mx-auto mt-4 max-w-2xl px-6 text-sm text-white/80"></figcaption>
        </figure>
        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 p-3 text-3xl text-white/70 hover:text-white md:right-8" data-lightbox-next aria-label="Next">&#8594;</button>
    </div>
    <script defer src="{{ asset('app.js') }}"></script>
</body>
</html>
