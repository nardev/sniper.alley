# sniperalley.photo

Static site for the Sniper Alley Photo Archive: photographs, photographers, and stories from Sarajevo under siege, 1992-1996.

Built with [HydePHP](https://hydephp.com). All content is plain Markdown files with a small frontmatter block, designed for manual editing. The site deploys to GitHub Pages automatically on every push to `main`.

## Requirements

- PHP 8.1 or newer
- Composer
- Node.js 18 or newer (only needed when you change CSS or JavaScript)

## Build and preview locally

```bash
composer install          # once, installs PHP dependencies
npm install               # once, installs asset tooling

php hyde build            # builds the whole site into _site/
php hyde serve            # live preview on http://localhost:8080
```

If you change anything in `resources/assets/` (CSS or JavaScript), rebuild the assets first:

```bash
npm run build             # compiles resources/assets into _media/app.css and app.js
```

Content changes (Markdown files, images) only need `php hyde build`.

## Where content lives

| What | Files | Shown at |
|---|---|---|
| Photographers | `content/photographers/<slug>.md` | /photographers |
| Stories Behind the Photos | `content/stories/<slug>.md` | /stories-behind-the-photos |
| In Memoriam | `content/memoriam/<slug>.md` | /in-memoriam |
| Latest (news feed) | `_posts/<slug>.md` | /latest |
| My Story, Mission, Contact, Donate | `content/pages/<name>.md` | fixed pages |
| Images | `_media/<section>/<slug>/` | copied to /media on build |
| Header photo rotation | `content/headers/photos.md` | background of the section landing pages |

The file name (without `.md`) becomes the page URL. Keep slugs lowercase with hyphens.

The home page and the Photographers, Stories Behind the Photos, and In Memoriam landing pages show a photo behind their title. Choose which photos rotate there by editing `content/headers/photos.md`: under `home`, `stories`, `photographers`, or `memoriam`, list image paths relative to `_media/` (for example `photographers/enrico-dagnino/photo.jpg`). One is picked at random each build. Leave a list empty to let the site pick a random photo from that section automatically.

On a photographer's gallery, opening a photo updates the address bar (for example `.../photographers/corinne-dufka.html#photo=...`). Copying that link and opening it reopens the gallery on that exact photo.

Frontmatter rules: put dates in quotes (`date: "2026-05-12"`), quote any text that contains a colon, and keep the `---` lines exactly as shown.

## Adding a photographer

Create `content/photographers/jane-doe.md`:

```markdown
---
name: "Jane Doe"
born: 1960
died: 2010          # omit this line if alive
role: Photojournalist
country: France     # optional
blurb: "One line shown on the card and listing."
portrait: portrait.jpg          # optional, file inside this photographer's media folder
quote: "A quote shown on the profile page."   # optional
featured: true      # optional, shows on the homepage
photos:
  - file: photo-01.jpg
    caption: "Sarajevo, 1993."
    credit: "(c) Jane Doe"
  - file: photo-02.jpg
    credit: "(c) Jane Doe"
---
Biography text in Markdown. Shown on the profile page.
```

Then put the image files in `_media/photographers/jane-doe/` (same folder name as the slug). Photos listed in `photos:` appear in the gallery with a lightbox. To upgrade a low quality archived image, replace the file in `_media/photographers/<slug>/` keeping the same filename.

## Adding a Stories Behind the Photos video

Create `content/stories/jane-doe-s2e6.md`:

```markdown
---
title: "Jane Doe"
photographer: jane-doe        # slug of a photographer file, optional
season: 2                     # groups the story on the page and colors the card chip
episode: 6
date: "2026-05-12"
youtube: dQw4w9WgXcQ          # the YouTube video id (the part after v= in the URL)
duration: "12:33"             # optional, shown on the card
excerpt: "One or two teaser sentences for cards and the featured block."
featured: true                # optional, exactly one story should have this
cover: cover.jpg              # optional, file in _media/stories/<slug>/; without it the YouTube thumbnail is used
---
Optional article text shown under the video, for example the crew credit.
```

Stories are grouped by season on the page (newest season first) and each card carries a season chip: Season 1 gray, Season 2 red. New seasons work automatically.

## Adding an In Memoriam page

Create `content/memoriam/jane-doe.md`:

```markdown
---
name: "Jane Doe"
born: 1960
died: 2010
banner: 00_JANE_EN.png        # wide image in _media/memoriam/jane-doe/, becomes the header background
cover: cover.jpg              # smaller image for listing tiles; falls back to the banner
photographer: jane-doe        # slug of the photographer file, links the gallery
excerpt: "Short line shown on tiles and under the name."
---
The tribute text, in Markdown.

![Jane Doe](/media/memoriam/jane-doe/01_JANE.jpg)

More text. Inline photos are ordinary Markdown images pointing into
/media/memoriam/jane-doe/, placed wherever they belong in the story.
```

Also add `memoriam: jane-doe` to the photographer file so the profile links back to the tribute.

## Posting to Latest

Create `_posts/my-post.md`:

```markdown
---
title: "New story coming soon"
description: "One or two sentences shown on the card."
category: press        # one of: press, photo, video, interview, article, memorial
date: "2026-07-15"
image: latest/my-post/cover.jpg   # optional, file under _media/
link: "https://example.com/article"    # optional, adds a Read the original button
---
The post body in Markdown.
```

The Latest page shows 12 posts at a time; the "View older posts" button reveals more. This is automatic.

### Embedding social media posts and videos

Raw HTML is allowed in post bodies, so paste the embed code the platform gives you:

- X/Twitter: paste the blockquote embed code from the post's "Embed post" menu (without the script tag; it loads automatically).
- Instagram: paste the embed code from the post's "Embed" menu (script loads automatically).
- Facebook: paste the post/video embed markup (SDK loads automatically).
- YouTube: paste the iframe embed code, or wrap it for correct proportions:

```html
<div style="aspect-ratio: 16/9"><iframe src="https://www.youtube-nocookie.com/embed/VIDEOID"
  style="width:100%;height:100%" allowfullscreen loading="lazy"></iframe></div>
```

## Publishing on GitHub

Everything publishes automatically:

1. Edit or add content files locally (or directly on github.com through the web editor).
2. Commit and push to `main` (or merge a pull request into `main`).
3. GitHub Actions builds the site and deploys it to GitHub Pages. Takes about two minutes. Check the Actions tab for progress.

You never commit `_site/`; the workflow builds it fresh every time. There is no separate "build the content" step to manage: every push rebuilds every page.

Preview a change before pushing with `php hyde build && php hyde serve`.

## Domain

The site currently serves from GitHub Pages. To activate `new.sniperalley.photo`:

1. Add a DNS CNAME record: `new.sniperalley.photo` pointing to `nardev.github.io`.
2. In the repository settings under Pages, set the custom domain to `new.sniperalley.photo` and enable Enforce HTTPS.
3. Set `'url' => 'https://new.sniperalley.photo'` in `config/hyde.php` and push, so sitemap and feed URLs are correct.

Switching later to `www.sniperalley.photo` is the same three steps with the new name.

## Archive scrape tooling

`tools/scrape/` contains the scripts that recovered the original site content from the Wayback Machine (the old WordPress site is behind bot protection). They are not part of the site build and only need to run again if content must be re-imported:

```bash
python3 tools/scrape/cdx_enum.py         # enumerate archived assets
python3 tools/scrape/build_manifest.py   # build the image manifest
python3 tools/scrape/download_images.py  # download images (resumable)
python3 tools/scrape/fetch_pages.py      # download page HTML
python3 tools/scrape/build_content.py    # regenerate content files (overwrites edits!)
```

`tools/scrape/coverage.md` reports what the archive did and did not preserve.
