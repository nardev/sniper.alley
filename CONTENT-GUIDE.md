# Content editing guide

This is a walkthrough for adding and editing site content: text, photos, and the links between
them. It assumes you will only touch Markdown files and images, not code. For build and deploy
commands, see [README.md](README.md).

## 1. Overview: what's where

The site has five kinds of content. Each one lives in its own folder under `content/`, except
"Our Work" posts and images:

| Content | Where the text lives | Where the images live |
|---|---|---|
| Photographers | `content/photographers/<slug>.md` | `_media/photographers/<slug>/` |
| Stories Behind the Photos | `content/stories/<slug>.md` | `_media/stories/<slug>/` |
| In Memoriam | `content/memoriam/<slug>.md` | `_media/memoriam/<slug>/` |
| Our Work (news feed) | `_posts/<slug>.md` | `_media/our-work/<category>/<slug>/` |
| Fixed pages (My Story, Contact, Donate) | `content/pages/<name>.md` | `_media/pages/<name>/` |
| Header photo rotation (no text, just a photo list) | `content/headers/photos.md` | uses photos already in `_media/` |

`<slug>` is the file name without `.md`. It becomes part of the page's web address, so keep it
lowercase with hyphens instead of spaces (`jane-doe.md`, not `Jane Doe.md`).

### Folders you don't need to touch

Everything else in the repository is code, build tooling, or configuration: `app/`, `config/`,
`resources/`, `vendor/`, `node_modules/`, `tools/`, `tests/`, `_site/` (the generated site, rebuilt
automatically), and the various `composer.*` / `package.*` / `hyde` files at the root. You will
never need to open these to add or edit content.

**One easy mix-up to avoid:** there is a root-level `_pages/` folder (with an underscore) that
holds page *templates* (code). It is a different thing from `content/pages/` (no underscore),
which is the folder you actually edit for the Contact, Donate, and My Story text. If you're
looking for where to change page wording, that's always `content/pages/`, never `_pages/`.

## 2. Rules that apply to every content file

- **Frontmatter block.** Every content file starts with a block fenced by two lines that just say
  `---`. That block holds fields like `name:`, `date:`, `title:`. Keep those two `---` lines
  exactly as they are; everything below the second one is the free-text body, written in Markdown.
- **Dates must be quoted:** `date: "2026-05-12"`, not `date: 2026-05-12`.
- **Quote any text that contains a colon** (`:`), for example `excerpt: "Season 2, Episode 5: The crossing"`.
  Without quotes, the colon can break the file.
- **Two different ways to reference an image, depending on where you use it:**
  - In a **frontmatter field** (`portrait:`, `cover:`, `banner:`, `image:`, a `file:` in a photo
    list), give just the file name, or a path relative to `_media/`. Example: `portrait.jpg` or
    `our-work/my-post/cover.jpg`.
  - In the **body text**, when placing a photo inline with Markdown (`![caption](path)`), the path
    must start with `/media/` (note: no underscore), because that's the published address of the
    file, not its location in the repo. Example: `![Jane Doe](/media/memoriam/jane-doe/01.jpg)`.
- **Image folders must be named after the slug.** If your content file is `jane-doe.md`, its images
  go in a folder called `jane-doe` under the matching `_media/` subfolder. The `_media/stories/`
  folder doesn't exist yet in the repo; create it the first time you add a custom cover photo to a
  story. `_media/our-work/` already exists with one subfolder per category, see section 3.4.
- Nothing needs to be "published" separately. Once a file is saved and pushed to `main`, the whole
  site rebuilds automatically (usually within about two minutes). See the "Publishing on GitHub"
  section in [README.md](README.md).

## 3. Categories

### 3.1 Photographers

**File:** `content/photographers/jane-doe.md`
**Images:** `_media/photographers/jane-doe/`

```markdown
---
name: "Jane Doe"
born: 1960
died: 2010
role: Photojournalist
country: France
blurb: "One line shown on the card and listing."
portrait: portrait.jpg
quote: "A quote shown on the profile page."
photos:
  - file: photo-01.jpg
    caption: "Sarajevo, 1993."
    credit: "(c) Jane Doe"
  - file: photo-02.jpg
    credit: "(c) Jane Doe"
memoriam: jane-doe
---
Biography text in Markdown, shown on the profile page.
```

Field rules:

| Field | Required | Notes |
|---|---|---|
| `name` | yes | Quote it. |
| `born` / `died` | no | Plain years. Omit `died` entirely if the photographer is alive. |
| `role` | yes | Short label, e.g. "Photojournalist", "War correspondent". |
| `country` | no | Shown on the profile page and used by the search/filter box. |
| `blurb` | no | One line, shown on the listing card and profile page. |
| `portrait` | no | File name inside this photographer's own media folder. If omitted, the site uses the first photo in `photos:` as the card image instead. |
| `quote` | no | Shown as a pull-quote on the profile page. |
| `photos` | yes (at least one, for a gallery) | A list. Each entry needs `file`; `caption` and `credit` are optional. **The order you list them in is the order they appear in the gallery.** |
| `memoriam` | no | The slug of a matching `content/memoriam/<slug>.md` file, to link "In Memoriam page" from the profile. Must match an existing memoriam file's slug exactly. |

Photographers are listed alphabetically by name automatically, you don't control the order.

There is a `featured:` field on a few existing photographer files left over from an earlier
version of the homepage design; it currently has no visible effect (the homepage instead shows a
random shuffled set of photographers on every visit). Don't rely on it to feature someone.

### 3.2 Stories Behind the Photos

**File:** `content/stories/jane-doe-s2e6.md` (the naming convention is `<photographer-slug>-s<season>e<episode>`)
**Images (only if you add a custom cover):** `_media/stories/jane-doe-s2e6/`

```markdown
---
title: "Jane Doe"
photographer: jane-doe
season: 2
episode: 6
date: "2026-05-12"
youtube: dQw4w9WgXcQ
duration: "12:33"
excerpt: "One or two teaser sentences for cards and the featured block."
featured: true
cover: cover.jpg
---
Optional article text shown under the video, for example the crew credit.
```

Field rules:

| Field | Required | Notes |
|---|---|---|
| `title` | yes | The photographer's name, usually. |
| `photographer` | no | Slug of a file in `content/photographers/`, exactly as the file is named (no `.md`). Links the story to that profile. |
| `season` / `episode` | yes | Whole numbers. Stories are grouped by season on the page, newest season first. Each season gets its own colored chip automatically, so a new season doesn't need any extra setup. |
| `date` | yes | Quoted, `"YYYY-MM-DD"`. |
| `youtube` | yes | Just the video ID, the part of the YouTube URL after `v=`. Do not paste the full URL, the page builds the embed link from this ID directly. |
| `duration` | no | Free text like `"12:33"`, shown as a small badge on the card. |
| `excerpt` | yes | One or two sentences, quoted if it contains a colon. |
| `featured` | no | Set on exactly one story at a time. It becomes the highlighted "latest episode" block on the homepage and the Stories page. If none is marked, the site just falls back to picking one automatically, so it's worth keeping this current. |
| `cover` | no | File name inside this story's own media folder. If omitted, the YouTube thumbnail is used instead, so you only need this for a custom cover image. |

### 3.3 In Memoriam

**File:** `content/memoriam/jane-doe.md`
**Images:** `_media/memoriam/jane-doe/`

```markdown
---
name: "Jane Doe"
born: 1960
died: 2010
banner: 00_JANE_EN.png
cover: cover.jpg
photographer: jane-doe
excerpt: "Short line shown on tiles and under the name."
---
The tribute text, in Markdown.

![Jane Doe](/media/memoriam/jane-doe/01_JANE.jpg)

More text. Inline photos are ordinary Markdown images pointing into
/media/memoriam/jane-doe/, placed wherever they belong in the story.
```

Field rules:

| Field | Required | Notes |
|---|---|---|
| `name`, `born`, `died` | yes | Same conventions as photographers. |
| `banner` | yes | Wide image in this page's media folder, used as the header background for the tribute. |
| `cover` | no | Smaller image used for the listing tile. If omitted, the `banner` is used, and if there's no `banner` either, the linked photographer's cover photo is used. |
| `photographer` | no | Slug of the matching file in `content/photographers/`. Also add `memoriam: jane-doe` to that photographer's own file, so the profile links back to this tribute; the two files should always point at each other. |
| `excerpt` | yes | Shown on listing tiles. |

Tributes are listed most-recently-deceased first, automatically, by the `died` year.

### 3.4 Our Work (news feed)

**File:** `_posts/my-post.md` (note: this is a root-level folder, not under `content/`)
**Images:** `_media/our-work/<category>/my-post/`

Since Our Work will collect many posts across different categories, its media folder is split up
by category first, then by post slug, so it doesn't turn into one giant flat folder. The six
category folders already exist and are ready to use:

```
_media/our-work/press/
_media/our-work/photo/
_media/our-work/video/
_media/our-work/interview/
_media/our-work/article/
_media/our-work/memorial/
_media/our-work/sketch/
```

For a new post, create a folder for its slug inside the matching category folder (for example
`_media/our-work/press/my-post/`) and put its images there.

```markdown
---
title: "New story coming soon"
description: "One or two sentences shown on the card."
category: press
date: "2026-07-15"
image: our-work/press/my-post/cover.jpg
link: "https://example.com/article"
---
The post body in Markdown.
```

Field rules:

| Field | Required | Notes |
|---|---|---|
| `title` | yes | |
| `description` | yes | Shown on the card in the feed. |
| `category` | yes | One of the values below. Controls the label, which filter tab the post appears under on the Our Work page, **and which folder its images live in.** |
| `date` | yes | Quoted, `"YYYY-MM-DD"`. Posts are shown newest first. |
| `image` | no | Path relative to `_media/`, including the category folder (see the path rules in section 2), shown as the card thumbnail. |
| `link` | no | Adds a "Read the original" button pointing at an outside article or source. |

Valid `category` values, each with its own media folder and filter tab: `press`, `photo`, `video`,
`interview`, `article`, `memorial`, `sketch` (short personal reflections/memoir pieces, distinct
from reporting). Using a value outside this list still works for the post itself, but it won't
get its own filter tab, and there's no matching folder for it, so avoid it unless you also add a
category folder and a filter tab to match (see below).

Adding a brand new category (an eighth one) means adding its filter tab and label in the templates,
which is a small code change, not a content change, so ask for that rather than just typing a new
word into `category:` and expecting a tab to appear for it.

The feed shows 12 posts at a time with a "View older posts" button; that pagination is automatic,
you don't need to do anything for it.

**Embedding social posts or videos in a post body:** raw HTML is allowed, so you can paste an
embed code directly into the body:

- X/Twitter: the blockquote embed from the post's "Embed post" menu (skip the `<script>` tag, it
  loads automatically).
- Instagram: the embed code from the post's "Embed" menu.
- Facebook: the post/video embed markup.
- YouTube: paste the iframe embed, or wrap it for correct proportions:
  ```html
  <div style="aspect-ratio: 16/9"><iframe src="https://www.youtube-nocookie.com/embed/VIDEOID"
    style="width:100%;height:100%" allowfullscreen loading="lazy"></iframe></div>
  ```

### 3.5 Fixed pages: My Story, Contact, Donate

**Files:** `content/pages/my-story.md`, `content/pages/contact.md`, `content/pages/donate.md`

These three files are a fixed set, each wired to its own page design. Adding a new file here (say,
`content/pages/team.md`) won't create a new page on the site, since there's no template for it.
If you need an actual new page, that's a code change, not a content change, so ask for that
separately.

"Mission" is not a separate file: it's simply the section of `my-story.md`'s body text, written
under a "MISSION" heading. Edit the wording directly in that file's body.

```markdown
---
title: "My Story"
background: bg_1.jpg
---
Body text in Markdown. Plain paragraphs, blank line between paragraphs.

![My Story](/media/pages/my-story/BROTHERS.jpg)

A caption line right after an image, on its own line, reads as a photo caption.
```

- `title` is used in the page's browser tab / navigation.
- `background` (only meaningful on `my-story.md`) is a file name inside `_media/pages/my-story/`,
  used as that page's header image.
- `contact.md` and `donate.md` don't use `background`, just `title` and body text.
- `donate.md` currently has both an English and a Bosnian version stacked in the same file, each
  under a `<p class="kicker">English</p>` / `<p class="kicker">Bosnian</p>` marker. Keep that
  pattern if you edit it, or drop the kicker lines if you no longer want two languages.

### 3.6 Header photo rotation

**File:** `content/headers/photos.md` (one file, not a folder of files)

Every section landing page (Home, Stories, Photographers, In Memoriam, Our Work) shows a random
photo behind its title. You choose the pool of photos it can pick from:

```yaml
---
home:
  - headers/cover-1.jpg
  - headers/cover-2.jpg
stories:
  - headers/cover-1.jpg
photographers:
  - headers/cover-1.jpg
memoriam:
  - headers/in-memoriam-1.jpg
our-work:
  - headers/cover-1.jpg
site:
  - headers/cover-1.jpg
---
```

- Paths are relative to `_media/`, same rule as other frontmatter image fields.
- One photo from a section's list is picked at random on every build, not every visit.
- Leave a section's list empty (or delete its lines) to let the site pick automatically from that
  section's own photo galleries instead.
- `site` is the fallback pool for pages that don't have their own list: Contact, Donate, My Story.
- The images themselves live in `_media/headers/`. To add a new one, put the file there first,
  then add its path to whichever section list(s) should use it.

## 4. Before you push: quick checklist

- File name is lowercase-with-hyphens, no spaces.
- Image folder name matches the content file's slug exactly (for Our Work, nested inside the
  matching category folder).
- Every date is quoted: `"2026-05-12"`.
- Any text with a colon in it is quoted.
- Frontmatter image fields use a bare path (`portrait.jpg`), inline body images use `/media/...`.
- If you linked a photographer to a memoriam page (or vice versa), both files point at each other.
- For a new story, `youtube:` is the bare video ID, not a full URL.
