@php
    $currentKey = ($page ?? null)?->getRouteKey() ?? '';
    $navItems = [
        'latest' => 'Latest',
        'stories-behind-photo' => 'Stories Behind Photo',
        'photographers' => 'Photographers',
        'in-memoriam' => 'In Memoriam',
        'my-story-mission' => 'My Story',
        'contact' => 'Contact',
    ];
@endphp
<header class="sticky top-0 z-50 bg-night text-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('index') }}" class="shrink-0 font-display text-xl font-bold tracking-tight">
            sniperalley<span class="text-accent">.photo</span>
        </a>
        <nav class="hidden items-center gap-6 lg:flex" aria-label="Main">
            @foreach ($navItems as $key => $label)
                <a href="{{ route($key) }}"
                   class="text-[13px] font-medium transition-colors hover:text-white {{ $currentKey === $key || str_starts_with($currentKey, $key.'/') ? 'text-white underline decoration-accent decoration-2 underline-offset-8' : 'text-white/70' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="flex items-center gap-3">
            <a href="{{ route('search') }}" class="p-1.5 text-white/70 transition-colors hover:text-white" aria-label="Search">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            </a>
            <a href="{{ route('donate') }}" class="bg-accent px-4 py-2 text-xs font-bold uppercase tracking-widest transition-colors hover:bg-accent-deep">Donate</a>
            <div class="hidden items-center gap-3 xl:flex">
                @include('components.social-links', ['class' => 'h-4 w-4 text-white/60 hover:text-white'])
            </div>
            <button type="button" class="p-1.5 lg:hidden" data-nav-toggle aria-label="Menu" aria-expanded="false">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
        </div>
    </div>
    <nav class="hidden border-t border-white/10 px-4 pb-4 lg:hidden" data-nav-menu aria-label="Mobile">
        @foreach ($navItems as $key => $label)
            <a href="{{ route($key) }}" class="block py-2.5 text-sm {{ $currentKey === $key ? 'text-white' : 'text-white/70' }}">{{ $label }}</a>
        @endforeach
        <div class="mt-3 flex gap-4">
            @include('components.social-links', ['class' => 'h-5 w-5 text-white/60 hover:text-white'])
        </div>
    </nav>
</header>
