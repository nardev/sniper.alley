<section class="bg-night text-white">
    <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 py-12 sm:px-6 lg:flex-row lg:items-center">
        <div class="max-w-xl">
            <h2 class="font-display text-2xl font-bold">{{ $heading ?? 'Why this archive exists' }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-white/70">{{ $text ?? 'Sniper Alley Photo project preserves the voices of photographers, the stories behind their images and historical memory of Sarajevo during the siege.' }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('my-story-mission') }}" class="btn-primary">Read My Story</a>
            <a href="{{ route('donate') }}" class="btn-outline text-white">Support the Archive</a>
        </div>
    </div>
</section>
