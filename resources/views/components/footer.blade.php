<footer class="bg-night text-white/70">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <p class="font-display text-xl font-bold text-white">sniperalley<span class="text-accent">.photo</span></p>
            <p class="mt-3 max-w-xs text-sm leading-relaxed">An archive of photographs, photographers, and stories from Sarajevo under siege, 1992-1996.</p>
        </div>
        <nav aria-label="Site">
            <p class="mb-3 text-xs font-bold uppercase tracking-widest text-white">Site</p>
            <ul class="space-y-2 text-sm">
                <li><a class="hover:text-white" href="{{ route('latest') }}">Latest</a></li>
                <li><a class="hover:text-white" href="{{ route('stories-behind-the-photos') }}">Stories Behind the Photos</a></li>
                <li><a class="hover:text-white" href="{{ route('photographers') }}">Photographers</a></li>
                <li><a class="hover:text-white" href="{{ route('in-memoriam') }}">In Memoriam</a></li>
            </ul>
        </nav>
        <nav aria-label="Information">
            <p class="mb-3 text-xs font-bold uppercase tracking-widest text-white">Information</p>
            <ul class="space-y-2 text-sm">
                <li><a class="hover:text-white" href="{{ route('my-story-mission') }}">My Story</a></li>
                <li><a class="hover:text-white" href="{{ route('contact') }}">Contact</a></li>
                <li><a class="hover:text-white" href="{{ route('donate') }}">Donate</a></li>
            </ul>
        </nav>
        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-widest text-white">Connect</p>
            <div class="flex gap-4">
                @include('components.social-links', ['class' => 'h-5 w-5 text-white/60 hover:text-white'])
            </div>
            <p class="mt-6 text-xs leading-relaxed">Write to us: <a class="text-white/90 hover:text-white" href="mailto:info@sniperalley.photo">info@sniperalley.photo</a></p>
        </div>
    </div>
    <div class="border-t border-white/10 px-4 py-5 text-center text-xs text-white/50">
        <p>Copyright &copy; SniperAlley {{ date('Y') }}. You may not redistribute or reproduce any of the contents in any form without prior written permission.</p>
    </div>
</footer>
