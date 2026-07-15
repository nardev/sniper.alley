<?php

namespace App;

use Hyde\Foundation\Concerns\HydeExtension;
use Hyde\Foundation\Kernel\PageCollection;
use Hyde\Pages\InMemoryPage;

/**
 * Generates one page per content item for the photographer, story,
 * and memoriam collections. Listing pages live in _pages as Blade views.
 */
class SiteExtension extends HydeExtension
{
    public function discoverPages(PageCollection $collection): void
    {
        foreach (Content::photographers() as $slug => $item) {
            $collection->addPage(InMemoryPage::make("photographers/{$slug}", [
                'title' => $item['name'] ?? $slug,
                'item' => $item,
                'navigation' => ['hidden' => true],
            ], view: 'pages.photographer'));
        }

        foreach (Content::stories() as $slug => $item) {
            $collection->addPage(InMemoryPage::make("stories-behind-photo/{$slug}", [
                'title' => $item['title'] ?? $slug,
                'item' => $item,
                'navigation' => ['hidden' => true],
            ], view: 'pages.story'));
        }

        foreach (Content::memoriam() as $slug => $item) {
            $collection->addPage(InMemoryPage::make("in-memoriam/{$slug}", [
                'title' => $item['name'] ?? $slug,
                'item' => $item,
                'navigation' => ['hidden' => true],
            ], view: 'pages.memorial'));
        }
    }
}
