<?php

namespace App\Actions;

use App\Content;
use Hyde\Framework\Features\BuildTasks\PostBuildTask;
use Hyde\Hyde;

/**
 * Writes _site/media/search.json, consumed by the search page and the
 * header search overlay. Indexes photographers (names, tags, photo
 * captions and credits), stories, memoriam pages, and latest posts.
 */
class GenerateSearchIndexBuildTask extends PostBuildTask
{
    public static string $message = 'Generating search index';

    public function handle(): void
    {
        $index = [];

        foreach (Content::photographers() as $slug => $item) {
            $captions = collect($item['photos'] ?? [])
                ->map(fn (array $photo): string => trim(($photo['caption'] ?? '').' '.($photo['credit'] ?? '')))
                ->filter()->implode(' ');
            $index[] = [
                'type' => 'photographer',
                'title' => $item['name'] ?? $slug,
                'url' => "photographers/{$slug}.html",
                'meta' => trim(($item['role'] ?? '').' '.($item['country'] ?? '')),
                'text' => mb_substr(trim(($item['blurb'] ?? '').' '.implode(' ', (array) ($item['tags'] ?? [])).' '.$captions.' '.strip_tags(Content::renderMarkdown($item['body'] ?? ''))), 0, 2000),
            ];
        }

        foreach (Content::stories() as $slug => $item) {
            $index[] = [
                'type' => 'story',
                'title' => $item['title'] ?? $slug,
                'url' => "stories-behind-photo/{$slug}.html",
                'meta' => Content::photographer($item['photographer'] ?? null)['name'] ?? ($item['photographer'] ?? ''),
                'text' => mb_substr(trim(($item['excerpt'] ?? '').' '.strip_tags(Content::renderMarkdown($item['body'] ?? ''))), 0, 2000),
            ];
        }

        foreach (Content::memoriam() as $slug => $item) {
            $index[] = [
                'type' => 'memoriam',
                'title' => $item['name'] ?? $slug,
                'url' => "in-memoriam/{$slug}.html",
                'meta' => trim(($item['born'] ?? '').' - '.($item['died'] ?? ''), ' -'),
                'text' => mb_substr(trim(($item['excerpt'] ?? '').' '.strip_tags(Content::renderMarkdown($item['body'] ?? ''))), 0, 2000),
            ];
        }

        foreach (Content::latest() as $post) {
            $index[] = [
                'type' => 'latest',
                'title' => $post->title(),
                'url' => "latest/{$post->identifier}.html",
                'meta' => (string) $post->matter('category', ''),
                'text' => mb_substr(strip_tags($post->markdown()->compile()), 0, 2000),
            ];
        }

        file_put_contents(
            Hyde::sitePath('media/search.json'),
            json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->createdSiteFile('_site/media/search.json');
    }
}
