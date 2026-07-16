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

    /** @return array<string, array> keyed by slug, sorted by name */
    public static function photographers(): array
    {
        return static::$cache['photographers'] ??= collect(static::collection('photographers'))
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
    public static function latest(): array
    {
        return static::$cache['latest'] ??= MarkdownPost::getLatestPosts()->all();
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
