@php
    /** @var \Hyde\Pages\MarkdownPost $post */
    $category = (string) $post->matter('category', 'update');
    $categoryLabels = [
        'announcement' => 'Announcement',
        'media-mention' => 'Media Mention',
        'film' => 'Film',
        'memorial' => 'Memorial',
        'update' => 'Update',
    ];
    $image = $post->matter('image');
    $external = $post->matter('link');
@endphp
<article class="group bg-white shadow-sm ring-1 ring-black/5 transition-shadow hover:shadow-lg" data-category="{{ $category }}">
    <a href="{{ route('latest/'.$post->identifier) }}" class="block">
        @if ($image && file_exists(\Hyde\Hyde::path('_media/'.$image)))
            <div class="aspect-video overflow-hidden bg-smoke">
                <img src="{{ asset($image) }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
            </div>
        @endif
        <div class="p-4">
            <p class="kicker">{{ $categoryLabels[$category] ?? ucfirst($category) }}</p>
            <h3 class="mt-1.5 font-display text-lg font-bold leading-snug group-hover:text-accent">{{ $post->title }}</h3>
            @if ($post->date)
                <p class="mt-1 text-xs text-mist">{{ $post->date->short }}</p>
            @endif
            @if ($post->matter('description'))
                <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-ink/70">{{ $post->matter('description') }}</p>
            @endif
        </div>
    </a>
</article>
