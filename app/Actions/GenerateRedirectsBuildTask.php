<?php

namespace App\Actions;

use App\Content;
use Hyde\Hyde;
use Hyde\Framework\Features\BuildTasks\PostBuildTask;
use Hyde\Support\Models\Redirect;

/**
 * Generates redirect pages for URLs from the old WordPress site so that
 * external links, bookmarks, and search results keep working.
 *
 * GitHub Pages cannot do server-side redirects, so each old URL gets a
 * small HTML page with a meta refresh pointing at the new location.
 * For every old path we write both "<path>.html" (covers links without a
 * trailing slash) and "<path>/index.html" (covers links with one).
 */
class GenerateRedirectsBuildTask extends PostBuildTask
{
    protected static string $message = 'Generating redirects for old site URLs';

    /** Old top-level pages and posts, mapped to their new locations. */
    private const PAGES = [
        'my-story' => '/my-story-mission.html',
        'mission' => '/my-story-mission.html',
        'stories' => '/stories-behind-the-photos.html',
        'video-en' => '/stories-behind-the-photos.html',
        'contact-us' => '/contact.html',
        'press' => '/our-work.html',
        'scatches' => '/our-work.html',
        'crtice' => '/our-work.html',
        'adnans-story' => '/our-work.html',
        '79-en' => '/our-work/79.html',
        'beauty' => '/our-work/beauty.html',
        'bro' => '/our-work/bro.html',
        'father-2' => '/our-work/father.html',
        'sjecanja' => '/our-work/memories.html',
        'the-watch' => '/our-work/the-watch.html',
        'topa' => '/our-work/topa.html',
    ];

    /** Old NextGEN gallery slugs that differ from the new photographer slugs. */
    private const RENAMED_GALLERIES = [
        'enrico-dagnino-7' => 'enrico-dagnino',
        'gabriel-bouys-2' => 'gabriel-bouys',
        'gilles-peress-3' => 'gilles-peress',
        'jack-picone-11' => 'jack-picone',
        'jean-baptiste-avril-3' => 'jean-baptiste-avril',
        'john-downing-1940-2020' => 'john-downing',
        'kevin-weaver-1963-2024' => 'kevin-weaver',
        'luc-delahaye-3' => 'luc-delahaye',
        'nigel-marple-1963-2025' => 'nigel-marple',
        'raffaele-ciriello-1959-2002' => 'raffaelle-ciriello-1959-2002',
        'romano-cagnoni-1935-2018' => 'romano-cagnoni',
        'teun-voeten-2' => 'teun-voeten',
        'thomas-haley-3' => 'thomas-haley',
        'vesa-oja-1953-2026' => 'vesa-oja',
        'wolfgang-bellwinkel' => 'wolfgang-bellwinkle',
        'yannis-behrakis-1960-2019' => 'yannis-behrakis',
        'zoran-filipovic-zoro' => 'zoran-filipovic',
    ];

    public function handle(): void
    {
        $count = 0;

        foreach (self::PAGES as $old => $destination) {
            $count += $this->redirect($old, $destination);
        }

        // Old listing pages exist at the same path as the new ones, but old
        // links have a trailing slash, which GitHub Pages resolves to a
        // missing index.html inside a directory of the same name.
        $count += $this->redirect('photographers/index', '/photographers.html');
        $count += $this->redirect('in-memoriam/index', '/in-memoriam.html');

        // Old memorial pages lived at /<name>-en/, e.g. /kurt-schork-en/.
        foreach (array_keys(Content::memoriam()) as $slug) {
            $count += $this->redirect("{$slug}-en", "/in-memoriam/{$slug}.html");
        }

        // Old galleries lived at /photographers/nggallery/photographers/<slug>/.
        $galleries = array_fill_keys(array_keys(Content::photographers()), null);
        foreach (self::RENAMED_GALLERIES as $old => $new) {
            $galleries[$old] = $new;
        }
        foreach ($galleries as $old => $new) {
            $count += $this->redirect(
                "photographers/nggallery/photographers/{$old}",
                '/photographers/'.($new ?? $old).'.html'
            );
        }

        $this->write("<info>{$count} redirect files created.</info> ");
    }

    /** Writes "<path>.html" and "<path>/index.html", skipping real pages. */
    private function redirect(string $path, string $destination): int
    {
        $count = 0;
        $targets = str_ends_with($path, '/index')
            ? [$path]
            : [$path, "{$path}/index"];

        foreach ($targets as $target) {
            $file = Hyde::sitePath("{$target}.html");
            if (file_exists($file)) {
                continue;
            }
            $dir = dirname($file);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            Redirect::create($target, $destination);
            $count++;
        }

        return $count;
    }
}
