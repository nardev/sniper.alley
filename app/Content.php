<?php

namespace App;

use Hyde\Hyde;
use Hyde\Markdown\Models\Markdown;
use Hyde\Markdown\Models\MarkdownDocument;
use Hyde\Pages\MarkdownPost;
use Hyde\Support\DataCollection;

/**
 * Central access point for the site content collections.
 *
 * Content lives in Markdown files with frontmatter:
 *   content/photographers/<slug>.md
 *   content/stories/<slug>.md
 *   content/memoriam/<slug>.md
 *   content/pages/<slug>.md
 *   _posts/<slug>.md (the Latest feed)
 */
class Content
{
    protected static array $cache = [];

    /** Changes newer than this many days are marked as recent on cards and galleries. */
    public const RECENT_DAYS = 30;

    /** @return array<string, array> keyed by slug, sorted by name */
    public static function photographers(): array
    {
        return static::$cache['photographers'] ??= collect(static::collection('photographers'))
            ->map(fn (array $item): array => static::withChangeDates($item))
            ->sortBy(fn (array $item): string => mb_strtolower($item['name'] ?? $item['slug']))
            ->all();
    }

    /** @return array<string, array> keyed by slug, newest first */
    public static function stories(): array
    {
        return static::$cache['stories'] ??= collect(static::collection('stories'))
            ->sortByDesc(fn (array $item): string => (string) ($item['date'] ?? ''))
            ->all();
    }

    /** @return array<string, array> keyed by slug, most recently deceased first */
    public static function memoriam(): array
    {
        return static::$cache['memoriam'] ??= collect(static::collection('memoriam'))
            ->sortByDesc(fn (array $item): int => (int) ($item['died'] ?? 0))
            ->all();
    }

    public static function photographer(?string $slug): ?array
    {
        return $slug ? (static::photographers()[$slug] ?? null) : null;
    }

    public static function memorial(?string $slug): ?array
    {
        return $slug ? (static::memoriam()[$slug] ?? null) : null;
    }

    public static function page(string $slug): ?array
    {
        $pages = static::$cache['pages'] ??= static::collection('pages');

        return $pages[$slug] ?? null;
    }

    /** @return array<array> stories credited to the given photographer slug */
    public static function storiesBy(string $slug): array
    {
        return array_values(array_filter(static::stories(), fn (array $story): bool => ($story['photographer'] ?? null) === $slug));
    }

    /** @return array<\Hyde\Pages\MarkdownPost> newest first */
    public static function ourWork(): array
    {
        return static::$cache['our-work'] ??= MarkdownPost::getLatestPosts()->all();
    }

    public static function featuredStory(): ?array
    {
        foreach (static::stories() as $story) {
            if ($story['featured'] ?? false) {
                return $story;
            }
        }

        return collect(static::stories())->first();
    }

    /**
     * Header background photos defined for a page in content/headers/photos.md.
     * Returns only paths that exist under _media/. $key is stories, photographers, memoriam, etc.
     *
     * @return array<string>
     */
    public static function headerPhotos(string $key): array
    {
        $headers = static::$cache['headers'] ??= static::collection('headers');
        $photos = $headers['photos'][$key] ?? [];

        return collect(is_array($photos) ? $photos : [])
            ->filter()
            ->map(fn ($path): string => ltrim((string) $path, '/'))
            ->filter(fn (string $path): bool => file_exists(Hyde::path("_media/{$path}")))
            ->values()->all();
    }

    /**
     * Pick one header background at random: a photo defined for $key in
     * content/headers/photos.md, or, when none are set, a random photo from the
     * given fallback pool (an iterable of _media-relative paths, nulls allowed).
     */
    public static function headerImage(string $key, iterable $fallback = []): ?string
    {
        $defined = static::headerPhotos($key);
        $pool = $defined !== []
            ? collect($defined)
            : collect($fallback)->filter()->values();

        return $pool->isNotEmpty() ? $pool->random() : null;
    }

    /** Path for an image belonging to a collection item, or null when absent. */
    public static function image(string $collection, string $slug, ?string $file): ?string
    {
        if (! $file) {
            return null;
        }
        $path = "{$collection}/{$slug}/{$file}";

        return file_exists(Hyde::path("_media/{$path}")) ? $path : null;
    }

    /** Tile image for a memorial: cover, else banner, else the photographer's cover. */
    public static function memorialTileImage(array $item): ?string
    {
        return static::image('memoriam', $item['slug'], $item['cover'] ?? null)
            ?? static::image('memoriam', $item['slug'], $item['banner'] ?? null)
            ?? (($photographer = static::photographer($item['photographer'] ?? null))
                ? static::photographerCover($photographer) : null);
    }

    /** Card image for a photographer: the portrait, or the first gallery photo. */
    public static function photographerCover(array $item): ?string
    {
        $cover = static::image('photographers', $item['slug'], $item['portrait'] ?? null);
        if (! $cover && ! empty($item['photos'])) {
            $cover = static::image('photographers', $item['slug'], $item['photos'][0]['file'] ?? null);
        }

        return $cover;
    }

    /** Cover image for a story: local cover file, else the YouTube thumbnail. */
    public static function storyCover(array $story): ?string
    {
        $local = static::image('stories', $story['slug'], $story['cover'] ?? null);
        if ($local) {
            return $local;
        }

        return null;
    }

    public static function storyThumbnail(array $story): ?string
    {
        $local = static::storyCover($story);
        if ($local) {
            return null;
        }

        return isset($story['youtube']) ? "https://i.ytimg.com/vi/{$story['youtube']}/hqdefault.jpg" : null;
    }

    public static function renderMarkdown(string $markdown): string
    {
        return Markdown::render($markdown);
    }

    /** True when the given YYYY-MM-DD date falls within the last RECENT_DAYS days. */
    public static function isRecent(?string $date): bool
    {
        return $date !== null && strtotime($date) >= strtotime('-'.static::RECENT_DAYS.' days');
    }

    /** "6 Nov 2026" for a YYYY-MM-DD date, or null. */
    public static function formatDate(?string $date): ?string
    {
        return $date ? date('j M Y', strtotime($date)) : null;
    }

    /**
     * Short label for a change: "New" when the file first appeared recently,
     * "Updated" when it changed recently, otherwise null.
     */
    public static function changeLabel(?string $added, ?string $updated): ?string
    {
        if (static::isRecent($added)) {
            return 'New';
        }

        return static::isRecent($updated) ? 'Updated' : null;
    }

    /**
     * Adds change dates taken from git history, so nothing has to be maintained by hand:
     *   added    date the photographer page file first appeared
     *   updated  latest change to the page file, the portrait, or any gallery photo
     *   photos.*.added / photos.*.updated  the same for each gallery image file
     *   new_photos  number of gallery photos added within RECENT_DAYS
     * Dates are YYYY-MM-DD strings, or null when git history is unavailable.
     */
    protected static function withChangeDates(array $item): array
    {
        $dates = static::gitDates();
        $slug = $item['slug'];
        $page = "content/photographers/{$slug}.md";

        $item['added'] = $dates['first'][$page] ?? null;
        $latest = $dates['latest'][$page] ?? null;
        $newPhotos = 0;

        $photos = [];
        foreach ((array) ($item['photos'] ?? []) as $photo) {
            if (is_array($photo) && ! empty($photo['file'])) {
                $file = "_media/photographers/{$slug}/{$photo['file']}";
                $photo['added'] = $dates['first'][$file] ?? null;
                $photo['updated'] = $dates['latest'][$file] ?? null;
                $latest = static::later($latest, $photo['updated']);
                $newPhotos += static::isRecent($photo['added']) ? 1 : 0;
            }
            $photos[] = $photo;
        }
        if (! empty($item['portrait'])) {
            $latest = static::later($latest, $dates['latest']["_media/photographers/{$slug}/{$item['portrait']}"] ?? null);
        }

        $item['photos'] = $photos;
        $item['updated'] = $latest;
        $item['new_photos'] = $newPhotos;

        return $item;
    }

    /** The later of two YYYY-MM-DD dates, ignoring nulls. */
    protected static function later(?string $a, ?string $b): ?string
    {
        if ($a === null || $b === null) {
            return $a ?? $b;
        }

        return $a >= $b ? $a : $b;
    }

    /**
     * First and latest commit date (YYYY-MM-DD) for every file under content/ and _media/,
     * read once per build from git history. Returns empty maps when git is missing, the
     * directory is not a repository, or the clone is shallow (a shallow clone would make
     * every file look like it changed in the latest commit).
     *
     * @return array{first: array<string, string>, latest: array<string, string>}
     */
    protected static function gitDates(): array
    {
        if (isset(static::$cache['git-dates'])) {
            return static::$cache['git-dates'];
        }

        $empty = ['first' => [], 'latest' => []];
        if (! function_exists('exec')) {
            return static::$cache['git-dates'] = $empty;
        }

        $git = 'git -C '.escapeshellarg(Hyde::path());

        $shallow = [];
        exec("{$git} rev-parse --is-shallow-repository 2>/dev/null", $shallow, $status);
        if ($status !== 0 || trim($shallow[0] ?? '') !== 'false') {
            return static::$cache['git-dates'] = $empty;
        }

        // One pass over the history, newest commit first. Each commit prints a marker line
        // with its date followed by the paths it touched. The first time a path is seen is
        // its latest change; the last time is when it was added.
        $lines = [];
        exec("{$git} -c core.quotePath=false log --no-renames --format=%x01%cd --date=short --name-only -- content _media 2>/dev/null", $lines, $status);
        if ($status !== 0) {
            return static::$cache['git-dates'] = $empty;
        }

        $first = $latest = [];
        $date = null;
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            if ($line[0] === "\x01") {
                $date = substr($line, 1);
                continue;
            }
            if ($date === null) {
                continue;
            }
            $latest[$line] ??= $date;
            $first[$line] = $date;
        }

        return static::$cache['git-dates'] = ['first' => $first, 'latest' => $latest];
    }

    /** @return array<string, array> keyed by slug */
    protected static function collection(string $name): array
    {
        $items = [];
        foreach (DataCollection::markdown($name) as $identifier => $document) {
            /** @var MarkdownDocument $document */
            $slug = basename($identifier, '.md');
            $items[$slug] = array_merge($document->matter()->toArray(), [
                'slug' => $slug,
                'body' => $document->markdown()->body(),
            ]);
        }

        return $items;
    }
}
