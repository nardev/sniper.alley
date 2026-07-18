#!/usr/bin/env python3
"""Convert the scraped Wayback data into the site's Markdown content tree.

Reads:  raw/gallery_images.json, raw/gallery/, raw/pages/
Writes: content/photographers, content/memoriam, content/pages,
        content/stories, _posts, _media/, tools/scrape/coverage.md

Idempotent: rerunning regenerates the generated files. Hand-edited files are
overwritten, so after the initial import treat the content tree as source.
"""
import html
import json
import re
import unicodedata
import urllib.parse
import urllib.request
from pathlib import Path

HERE = Path(__file__).parent
RAW = HERE / "raw"
ROOT = HERE.parent.parent
MEDIA = ROOT / "_media"
CONTENT = ROOT / "content"
POSTS = ROOT / "_posts"

UA = {"User-Agent": "Mozilla/5.0 (compatible; sniperalley-rebuild/1.0)"}

SKIP_GALLERIES = {"homepage-gallery"}


def log(msg: str) -> None:
    print(msg, flush=True)


def ascii_slugify(value: str) -> str:
    value = unicodedata.normalize("NFKD", value).encode("ascii", "ignore").decode()
    value = re.sub(r"[^a-zA-Z0-9]+", "-", value).strip("-").lower()
    return value or "item"


def ascii_filename(value: str) -> str:
    value = urllib.parse.unquote(value)
    base, dot, ext = value.rpartition(".")
    base = unicodedata.normalize("NFKD", base).encode("ascii", "ignore").decode()
    base = re.sub(r"[^a-zA-Z0-9_-]+", "_", base).strip("_") or "photo"
    return f"{base}.{ext.lower()}" if dot else base


def clean_text(text: str) -> str:
    text = html.unescape(text)
    # mis-encoded CP1252 smart punctuation shows up as C1 control chars
    text = text.replace("\x91", "'").replace("\x92", "'").replace("\x93", '"').replace("\x94", '"')
    text = re.sub(r"[\x80-\x9f]", "", text)
    text = text.replace("\u2014", " - ").replace("\u2013", "-")
    text = text.replace(" ", " ")
    text = re.sub(r"[ \t]+", " ", text)
    text = text.replace("[email protected]", "info@sniperalley.photo")
    return text.strip()


def yaml_quote(value: str) -> str:
    value = re.sub(r"\s*\n\s*", " ", str(value))
    return '"' + value.replace('\\', '\\\\').replace('"', '\\"') + '"'


def strip_wayback(url: str) -> str:
    return re.sub(r"^https?://web\.archive\.org/web/[0-9a-z_]+/", "", url)


import time


def wayback_fetch(url: str, timestamp: str = "2") -> bytes | None:
    wb = f"https://web.archive.org/web/{timestamp}im_/{url}"
    for attempt in range(4):
        try:
            req = urllib.request.Request(wb, headers=UA)
            with urllib.request.urlopen(req, timeout=90) as response:
                return response.read()
        except urllib.error.HTTPError as error:
            if error.code == 404:
                return None
            time.sleep(6 * (attempt + 1))
        except Exception:
            time.sleep(6 * (attempt + 1))
    return None


def read_page(slug: str) -> str | None:
    path = RAW / "pages" / f"{slug}.html"
    if not path.exists():
        return None
    raw = path.read_text(encoding="utf-8", errors="replace")
    raw = re.sub(r"<!-- BEGIN WAYBACK TOOLBAR INSERT -->.*?<!-- END WAYBACK TOOLBAR INSERT -->", "", raw, flags=re.S)
    return raw


def extract_paragraphs(body: str) -> list[str]:
    """Return cleaned text paragraphs from a WordPress page body."""
    body = re.sub(r"<script.*?</script>", " ", body, flags=re.S)
    body = re.sub(r"<style.*?</style>", " ", body, flags=re.S)
    paragraphs = []
    for match in re.finditer(r"<p[^>]*>(.*?)</p>", body, flags=re.S):
        text = re.sub(r"<br\s*/?>", "\n", match.group(1))
        text = re.sub(r"<[^>]+>", "", text)
        text = clean_text(text)
        if text:
            paragraphs.append(text)
    return paragraphs


def page_main(raw: str) -> str:
    """Best-effort slice of the page's main content area."""
    for marker in ('id="rt-main"', 'class="rt-main', "<main", 'id="content"'):
        index = raw.find(marker)
        if index != -1:
            end = raw.find('id="rt-footer', index)
            return raw[index:end if end != -1 else len(raw)]
    return raw


# ---------------------------------------------------------------- photographers

def load_photographer_meta() -> dict:
    """slug -> {name, born, died, bio, preview} parsed from the album page."""
    raw = read_page("photographers")
    if raw is None:
        log("WARNING: photographers.html not fetched, using slug-derived names")
        return {}
    meta = {}
    tiles = re.split(r'<div class="image_container">', raw)
    for tile in tiles[1:]:
        slug_match = re.search(r"/photographers/nggallery/photographers/([^\"/]+)", tile)
        if not slug_match:
            continue
        slug = urllib.parse.unquote(slug_match.group(1))
        caption_match = re.search(r'<span class="caption_link"><a[^>]*>\s*(.*?)\s*</a>', tile, flags=re.S)
        caption = clean_text(re.sub(r"<[^>]+>", "", caption_match.group(1))) if caption_match else ""
        caption = re.sub(r"https?://\S+", " ", caption).replace('">', " ")
        caption = max((part.strip() for part in caption.splitlines()), key=len, default="").strip()
        bio_match = re.search(r'<a href="[^"]*nggallery[^"]*" title="([^"]{40,})"', tile)
        bio = clean_text(bio_match.group(1)) if bio_match else ""
        preview_match = re.search(r"/gallery/[^/\"]+/thumbs/thumbs_([^\"]+?\.(?:jpg|jpeg|png|gif|webp))", tile, flags=re.I)
        preview = ascii_filename(preview_match.group(1)) if preview_match else None

        name, born, died = caption, None, None
        dates_match = re.search(r"\((\d{4})\s*-\s*(\d{4})\)", caption)
        if dates_match:
            born, died = int(dates_match.group(1)), int(dates_match.group(2))
            name = caption[: dates_match.start()].strip()
        meta[slug] = {"name": name, "born": born, "died": died, "bio": bio, "preview": preview}
    log(f"photographer meta parsed for {len(meta)} galleries")
    return meta


FEATURED_SLUGS = [
    "anja-niedringhaus-1965-2014",
    "tom-stoddart-1953-2021",
    "luc-delahaye",
    "annie-leibovitz",
]

# The original site's slugs misspell some names; correct display names here.
NAME_OVERRIDES = {
    "jean-baptiste-avril": "Jean-Baptiste Avril",
    "raffaelle-ciriello-1959-2002": "Raffaele Ciriello",
    "wolfgang-bellwinkle": "Wolfgang Bellwinkel",
    "zoran-filipovic": "Zoran Filipović Zoro",
}

# Duplicate galleries on the original site merged into one page.
GALLERY_ALIASES = {
    "tom-stoddart": "tom-stoddart-1953-2021",
}


def convert_photographers(manifest: list[dict], meta: dict) -> dict:
    photographers = {}
    stats = {}
    for item in manifest:
        slug = GALLERY_ALIASES.get(item["gallery"], item["gallery"])
        if slug in SKIP_GALLERIES:
            continue
        source = RAW / "gallery" / item["gallery"] / item["file"]
        entry = photographers.setdefault(slug, {"photos": [], "missing": 0, "kinds": {}})
        if not source.exists() or source.stat().st_size == 0:
            entry["missing"] += 1
            continue
        dest_name = ascii_filename(item["file"])
        dest = MEDIA / "photographers" / slug / dest_name
        dest.parent.mkdir(parents=True, exist_ok=True)
        if not dest.exists():
            dest.write_bytes(source.read_bytes())
        entry["photos"].append(dest_name)
        kind = "original" if item["kind"] == "original" else "cache"
        entry["kinds"][kind] = entry["kinds"].get(kind, 0) + 1

    (CONTENT / "photographers").mkdir(parents=True, exist_ok=True)
    for slug, entry in sorted(photographers.items()):
        info = meta.get(slug, {})
        fallback_name = re.sub(r"-\d{4}-\d{4}$", "", slug).replace("-", " ").title()
        name = NAME_OVERRIDES.get(slug) or info.get("name") or fallback_name
        born, died = info.get("born"), info.get("died")
        if born is None:
            dates_match = re.search(r"-(\d{4})-(\d{4})$", slug)
            if dates_match:
                born, died = int(dates_match.group(1)), int(dates_match.group(2))
        photos = sorted(set(entry["photos"]))
        preview = info.get("preview")
        portrait = preview if preview and preview in photos else None

        lines = ["---", f"name: {yaml_quote(name)}"]
        if born:
            lines.append(f"born: {born}")
        if died:
            lines.append(f"died: {died}")
        lines.append("role: Photojournalist")
        if portrait:
            lines.append(f"portrait: {portrait}")
        if slug in FEATURED_SLUGS:
            lines.append("featured: true")
        lines.append("photos:")
        for photo in photos:
            lines.append(f"  - file: {photo}")
            lines.append(f'    credit: {yaml_quote("(c) " + name)}')
        lines.append("---")
        body = info.get("bio") or ""
        content = "\n".join(lines) + "\n" + (body + "\n" if body else "")
        (CONTENT / "photographers" / f"{slug}.md").write_text(content, encoding="utf-8")
        stats[slug] = {
            "photos": len(photos),
            "missing": entry["missing"],
            "kinds": entry["kinds"],
            "named": slug in meta,
        }
    log(f"photographers written: {len(stats)}")
    return stats


# ------------------------------------------------------------------- memoriam

MEMORIAL_SLUGS = [
    "karsten-thielker", "yannis-behrakis", "romano-cagnoni", "abbas-attar",
    "emil-grebenar", "hidajet-delic", "gerard-rondeau", "anja-niedringhaus",
    "danilo-krstanovic", "paul-marchand", "alexandra-boulat", "kurt-schork",
    "salko-hondo", "jordi-pujol-puente",
]


SKIP_IMAGE_HINTS = ("LOGO", "logo", "icon", "Icon", "COVER-SOCIAL")


def parse_memorial_covers() -> dict[str, str]:
    """slug -> tile cover image url, from the archived /in-memoriam/ listing."""
    raw = read_page("in-memoriam")
    covers = {}
    if raw is None:
        return covers
    for match in re.finditer(
            r'<a href="[^"]*?sniperalley\.photo/([a-z0-9-]+?)(?:-en)?/"[^>]*class="photographers_box">'
            r'\s*<div class="image" style="background-image: url\(\'([^\']+)\'\);?"',
            raw):
        covers[match.group(1)] = strip_wayback(match.group(2))
    return covers


def parse_tribute_sections(raw: str) -> list[dict]:
    """Ordered fullpage.js sections: [{image: url|None, paragraphs: [...]}]."""
    css_backgrounds = {}
    for match in re.finditer(
            r"\.mcw-fp-section_([0-9a-f]+)\s+\.fp-bg\{[^}]*?background-image:url\(([^)]+)\)", raw):
        css_backgrounds[match.group(1)] = strip_wayback(match.group(2).strip("'\""))

    sections = []
    parts = re.split(r'<div[^>]+class="[^"]*mcw-fp-section[^"]*mcw-fp-section_([0-9a-f]+)[^"]*"', raw)
    for i in range(1, len(parts), 2):
        section_hash, chunk = parts[i], parts[i + 1]
        image = css_backgrounds.get(section_hash)
        if image and (any(hint in image for hint in SKIP_IMAGE_HINTS)
                      or not re.search(r"/wp-content/uploads/.*\.(?:jpg|jpeg|png)$", image, flags=re.I)):
            image = None
        sections.append({"image": image, "paragraphs": extract_paragraphs(chunk)})
    return sections


def convert_memoriam(photographer_meta: dict) -> list[str]:
    (CONTENT / "memoriam").mkdir(parents=True, exist_ok=True)
    missing = []
    tile_covers = parse_memorial_covers()
    name_to_gallery = {}
    for gallery_slug, info in photographer_meta.items():
        if info.get("name"):
            name_to_gallery[ascii_slugify(info["name"])] = gallery_slug

    for slug in MEMORIAL_SLUGS:
        raw = read_page(f"{slug}-en") or read_page(slug)
        if raw is None:
            missing.append(slug)
            continue
        title_match = re.search(r"<title[^>]*>(.*?)[|<]", raw, flags=re.S)
        name = clean_text(title_match.group(1)) if title_match else slug.replace("-", " ").title()
        main = page_main(raw)
        sections = parse_tribute_sections(raw)
        paragraphs = [p for section in sections for p in section["paragraphs"]] or extract_paragraphs(main)
        born = died = None
        joined = " ".join(paragraphs[:3]) + " " + name
        dates_match = re.search(r"\((?:[^)]*?)?(\d{4})\s*-\s*(?:[^)]*?)?(\d{4})\)", joined) or \
            re.search(r"(\d{4})\s*-\s*(\d{4})", joined)
        if dates_match:
            born, died = int(dates_match.group(1)), int(dates_match.group(2))
        name = re.sub(r"\s*\([^)]*\)\s*$", "", name).strip()

        gallery_slug = name_to_gallery.get(ascii_slugify(name))
        if gallery_slug is None:
            for key, value in name_to_gallery.items():
                if ascii_slugify(name) in key or key in ascii_slugify(name):
                    gallery_slug = value
                    break

        media_dir = MEDIA / "memoriam" / slug

        def download_image(url: str, filename: str) -> str | None:
            dest = media_dir / filename
            if dest.exists() and dest.stat().st_size > 1000:
                return filename
            data = wayback_fetch(url)
            if data and len(data) > 1000:
                dest.parent.mkdir(parents=True, exist_ok=True)
                dest.write_bytes(data)
                return filename
            return None

        # banner: first section image; inline: the rest, in slide order
        section_images = [section["image"] for section in sections if section["image"]]
        banner_rel = None
        if section_images:
            banner_rel = download_image(section_images[0], ascii_filename(section_images[0].rsplit("/", 1)[-1]))

        # tile cover from the listing page, fallback to banner
        cover_rel = None
        if slug in tile_covers:
            cover_rel = download_image(tile_covers[slug], "cover.jpg")
        legacy_cover = media_dir / "cover.jpg"
        if cover_rel is None and legacy_cover.exists():
            cover_rel = "cover.jpg"

        # body: interleave section text with inline images
        body_parts = []
        first_image_used = False
        for section in sections:
            if section["image"]:
                if not first_image_used:
                    first_image_used = True
                else:
                    filename = download_image(section["image"], ascii_filename(section["image"].rsplit("/", 1)[-1]))
                    if filename:
                        body_parts.append(f"![{name}](/media/memoriam/{slug}/{filename})")
            body_parts.extend(section["paragraphs"])
        if not body_parts:
            body_parts = paragraphs

        excerpt = next((p for p in paragraphs if len(p) > 40), paragraphs[0] if paragraphs else "")[:200]
        lines = ["---", f"name: {yaml_quote(name)}"]
        if born:
            lines.append(f"born: {born}")
        if died:
            lines.append(f"died: {died}")
        if banner_rel:
            lines.append(f"banner: {banner_rel}")
        if cover_rel:
            lines.append(f"cover: {cover_rel}")
        if gallery_slug:
            lines.append(f"photographer: {gallery_slug}")
        if excerpt:
            lines.append(f"excerpt: {yaml_quote(excerpt)}")
        lines.append("---")
        (CONTENT / "memoriam" / f"{slug}.md").write_text(
            "\n".join(lines) + "\n" + "\n\n".join(body_parts) + "\n", encoding="utf-8")
    log(f"memoriam written: {len(MEMORIAL_SLUGS) - len(missing)} (missing: {missing})")
    return missing


# ---------------------------------------------------------------------- pages

def convert_page(slug: str, out_name: str, title: str, media_subdir: str | None = None) -> bool:
    raw = read_page(slug)
    if raw is None:
        return False
    main = page_main(raw)
    paragraphs = extract_paragraphs(main)
    body_parts = list(paragraphs)
    if media_subdir:
        image_urls = re.findall(r'(?:data-src|src)="([^"]*?/wp-content/uploads/[^"]+?\.(?:jpg|jpeg|png))"', main, flags=re.I)
        seen = set()
        index = 0
        for image_url in image_urls:
            url = strip_wayback(image_url)
            if url in seen or "LOGO" in url:
                continue
            seen.add(url)
            data = wayback_fetch(url)
            if data and len(data) > 1000:
                index += 1
                dest = MEDIA / "pages" / media_subdir / f"image-{index:02d}.jpg"
                dest.parent.mkdir(parents=True, exist_ok=True)
                dest.write_bytes(data)
        if index:
            body_parts.append("## Photographs")
            body_parts.append("The photographs from the original page. Move them into "
                              "place in the text wherever they belong.")
            for i in range(1, index + 1):
                body_parts.append(f"![Photo {i}](/media/pages/{media_subdir}/image-{i:02d}.jpg)")

    lines = ["---", f"title: {yaml_quote(title)}", "---"]
    (CONTENT / "pages" / f"{out_name}.md").write_text("\n".join(lines) + "\n" + "\n\n".join(body_parts) + "\n", encoding="utf-8")
    return True


FOOTER_HINTS = ("All Rights Reserved", "SWITCH THE LANGUAGE")


def convert_my_story() -> None:
    """My Story keeps its slide backgrounds: first becomes the page hero
    background, the rest are placed inline at their narrative positions."""
    raw = read_page("my-story")
    if raw is None:
        return
    sections = parse_tribute_sections(raw)
    if not any(section["image"] for section in sections):
        convert_page("my-story", "my-story", "My Story", media_subdir="my-story")
        return
    media_dir = MEDIA / "pages" / "my-story"
    for stale in media_dir.glob("image-*.jpg"):
        stale.unlink()

    def download_image(url: str) -> str | None:
        filename = ascii_filename(url.rsplit("/", 1)[-1])
        dest = media_dir / filename
        if dest.exists() and dest.stat().st_size > 1000:
            return filename
        data = wayback_fetch(url)
        if data and len(data) > 1000:
            dest.parent.mkdir(parents=True, exist_ok=True)
            dest.write_bytes(data)
            return filename
        return None

    background = None
    body_parts = []
    first_image_used = False
    for section in sections:
        if section["image"]:
            filename = download_image(section["image"])
            if filename:
                if not first_image_used:
                    background = filename
                    first_image_used = True
                else:
                    body_parts.append(f"![My Story](/media/pages/my-story/{filename})")
        for paragraph in section["paragraphs"]:
            if any(hint in paragraph for hint in FOOTER_HINTS) or paragraph.startswith(("©", "(c) ")):
                continue
            body_parts.append(paragraph)

    lines = ["---", 'title: "My Story"']
    if background:
        lines.append(f"background: {background}")
    lines.append("---")
    (CONTENT / "pages" / "my-story.md").write_text(
        "\n".join(lines) + "\n" + "\n\n".join(body_parts) + "\n", encoding="utf-8")
    log(f"my-story written with {sum(1 for s in sections if s['image'])} images")


def convert_survivor_stories() -> None:
    """Append full survivor stories (linked from the old Stories page) to stories.md."""
    raw = read_page("stories")
    if raw is None:
        return
    links = re.findall(r'href="[^"]*?sniperalley\.photo/([a-z0-9-]+)/"', raw)
    candidates = [slug for slug in dict.fromkeys(links)
                  if (slug.endswith("-story") or slug.startswith("story-"))
                  and slug not in ("my-story", "stories")]
    sections = []
    for slug in candidates:
        story_raw = read_page(slug)
        if story_raw is None:
            continue
        title_match = re.search(r"<title[^>]*>(.*?)[|<]", story_raw, flags=re.S)
        title = clean_text(title_match.group(1)) if title_match else slug.replace("-", " ").title()
        paragraphs = extract_paragraphs(page_main(story_raw))
        if paragraphs:
            sections.append(f"## {title}\n\n" + "\n\n".join(paragraphs))
    if sections:
        target = CONTENT / "pages" / "stories.md"
        existing = target.read_text(encoding="utf-8") if target.exists() else "---\ntitle: \"Stories of Others\"\n---\n"
        target.write_text(existing.rstrip() + "\n\n" + "\n\n".join(sections) + "\n", encoding="utf-8")
        log(f"survivor stories appended: {len(sections)}")


def convert_sketches() -> None:
    raw = read_page("scatches")
    if raw is None:
        return
    main = page_main(raw)
    sections = []
    for match in re.finditer(r'<h2[^>]*>\s*(?:<a[^>]*href="([^"]*)"[^>]*>)?(.*?)(?:</a>\s*)?</h2>(.*?)(?=<h2|$)', main, flags=re.S):
        href, heading_html, rest = match.groups()
        heading = clean_text(re.sub(r"<[^>]+>", "", heading_html))
        if not heading or "SWITCH" in heading:
            continue
        paragraphs = None
        if href:
            slug = strip_wayback(href).rstrip("/").rsplit("/", 1)[-1]
            full = read_page(slug)
            if full is not None:
                paragraphs = extract_paragraphs(page_main(full))
        if not paragraphs:
            paragraphs = [p.replace("[...]", "").replace("[…]", "").strip()
                          for p in extract_paragraphs(rest)[:2]]
        text = "\n\n".join(p for p in paragraphs if p)
        if heading and text:
            sections.append(f"## {heading}\n\n{text}")
    if sections:
        content = "---\ntitle: \"Sketches\"\n---\n" + "\n\n".join(sections) + "\n"
        (CONTENT / "pages" / "sketches.md").write_text(content, encoding="utf-8")
        log(f"sketches written with {len(sections)} sections")


# --------------------------------------------------------------------- stories

# The definitive episode list for Stories Behind the Photos.
# (video id, title, season, episode, date, photographer slug)
# Video ids and dates come from the channel feed:
# https://www.youtube.com/feeds/videos.xml?channel_id=UCwUNPs8hHJyMYKtrgxnOnbQ
EPISODES = [
    ("APwSekSbogc", "Enrico Dagnino", 1, 1, "2019-12-05", "enrico-dagnino"),
    ("jRzAKSCYCJs", "Thomas Haley", 1, 2, "2020-09-02", "thomas-haley"),
    ("a_g3yxKvHnk", "Christopher Morris", 1, 3, "2021-01-22", "christopher-morris"),
    ("nzopsXy_jCQ", "Enric Martí", 1, 4, "2023-11-18", "enric-marti"),
    ("xdtQLJvfgLs", "Peter Kullmann", 1, 5, "2024-03-02", "peter-kullmann"),
    ("AapubaSUgJQ", "Zoran Filipović Zoro", 1, 6, "2024-08-09", "zoran-filipovic"),
    ("mYTmjTLQbUo", "Staffan Löfving", 2, 1, "2024-12-27", "staffan-lofving"),
    ("nlqVFJr1hEs", "Mario Boccia", 2, 2, "2025-01-03", "mario-boccia"),
    ("7d4JUepxmX4", "David Barker", 2, 3, "2025-02-28", "david-barker"),
    ("Um20d8BJ2co", "Paul Lowe", 2, 4, "2025-04-04", "paul-lowe-1963-2024"),
    ("jB-onZx6XuA", "Rikard Larma", 2, 5, "2025-09-27", "rikard-larma"),
]


def convert_stories() -> None:
    (CONTENT / "stories").mkdir(parents=True, exist_ok=True)
    newest = max(EPISODES, key=lambda episode: episode[4])
    for video_id, name, season, episode, date, photographer in EPISODES:
        slug = f"{ascii_slugify(name)}-s{season}e{episode}"
        lines = [
            "---",
            f"title: {yaml_quote(name)}",
            f"photographer: {photographer}",
            f"season: {season}",
            f"episode: {episode}",
            f"youtube: {video_id}",
            f'date: "{date}"',
            f"excerpt: {yaml_quote(f'{name} tells the story behind the photo. Season {season}, episode {episode}.')}",
        ]
        if video_id == newest[0]:
            lines.append("featured: true")
        lines.append("---")
        body = "Lead DOP & Videographer Emir Jordamović.\n"
        (CONTENT / "stories" / f"{slug}.md").write_text("\n".join(lines) + "\n" + body, encoding="utf-8")
    log(f"stories written: {len(EPISODES)}")


# ---------------------------------------------------------------------- press

MONTHS = {"1": "January", "2": "February", "3": "March", "4": "April", "5": "May", "6": "June",
          "7": "July", "8": "August", "9": "September", "10": "October", "11": "November", "12": "December"}


def convert_press() -> int:
    raw = read_page("press")
    if raw is None:
        log("WARNING: press.html not fetched, skipping press posts")
        return 0
    main = page_main(raw)
    main = re.sub(r"<script.*?</script>", " ", main, flags=re.S)
    POSTS.mkdir(exist_ok=True)
    count = 0
    seen_slugs = set()
    pattern = re.compile(
        r"(\d{1,2})\.(\d{1,2})\.(\d{4})\.?\s*/\s*([^/<]+?)\s*/\s*(\w+)\s*"
        r"(?:<[^>]+>\s*)*<a[^>]*href=\"([^\"]+)\"[^>]*>(.*?)</a>",
        flags=re.S)
    for match in pattern.finditer(main):
        day, month, year, outlet, language, link, title_html = match.groups()
        title = clean_text(re.sub(r"<[^>]+>", "", title_html))
        outlet = clean_text(outlet)
        if not title or len(title) < 4:
            continue
        link = strip_wayback(html.unescape(link))
        date = f"{year}-{int(month):02d}-{int(day):02d}"
        slug = ascii_slugify(f"{outlet}-{title}")[:70]
        if slug in seen_slugs:
            continue
        seen_slugs.add(slug)
        content = "\n".join([
            "---",
            f"title: {yaml_quote(title)}",
            f"description: {yaml_quote(f'{outlet} ({language}) covered the Sniper Alley project.')}",
            "category: media-mention",
            f'date: "{date}"',
            f"link: {yaml_quote(link)}",
            "---",
            f"Published by {outlet} in {language} on {MONTHS.get(str(int(month)), month)} {int(day)}, {year}.",
            "",
        ])
        (POSTS / f"{slug}.md").write_text(content, encoding="utf-8")
        count += 1
    log(f"press posts written: {count}")
    return count


# ----------------------------------------------------------------- site assets

def convert_site_assets(manifest: list[dict]) -> None:
    home_gallery = [item for item in manifest if item["gallery"] == "homepage-gallery"]
    preferred = [item for item in home_gallery if "10_HOME_LD" in item["file"]] or home_gallery
    for item in preferred:
        source = RAW / "gallery" / item["gallery"] / item["file"]
        if source.exists() and source.stat().st_size > 10000:
            dest = MEDIA / "site" / "hero.jpg"
            dest.parent.mkdir(parents=True, exist_ok=True)
            dest.write_bytes(source.read_bytes())
            log(f"hero image set from {item['file']}")
            break

    logo = wayback_fetch("https://sniperalley.photo/wp-content/uploads/2019/07/LOGO-black-1.jpg")
    if logo and len(logo) > 1000:
        (MEDIA / "site" / "logo.jpg").write_bytes(logo)
        log("logo downloaded")


# -------------------------------------------------------------------- coverage

def write_coverage(photographer_stats: dict, memoriam_missing: list[str]) -> None:
    failures = json.loads((RAW / "download_failures.json").read_text()) if (RAW / "download_failures.json").exists() else []
    lines = [
        "# Scrape coverage report",
        "",
        "Source: Wayback Machine (web.archive.org). Archived cache renditions are",
        "at most 1200px wide. Replace any image in _media/photographers/<slug>/",
        "with a full quality original to upgrade it, keeping the same filename.",
        "",
        f"- Photographer galleries: {len(photographer_stats)}",
        f"- Photos copied: {sum(stat['photos'] for stat in photographer_stats.values())}",
        f"- Full originals: {sum(stat['kinds'].get('original', 0) for stat in photographer_stats.values())}",
        f"- Cache renditions: {sum(stat['kinds'].get('cache', 0) for stat in photographer_stats.values())}",
        f"- Known images that failed to download: {len(failures)}",
        f"- Memorial pages with no archived copy: {', '.join(memoriam_missing) or 'none'}",
        "",
        "## Galleries",
        "",
        "| Gallery | Photos | Originals | Cache | Failed |",
        "|---|---|---|---|---|",
    ]
    for slug, stat in sorted(photographer_stats.items()):
        lines.append(f"| {slug} | {stat['photos']} | {stat['kinds'].get('original', 0)} | "
                     f"{stat['kinds'].get('cache', 0)} | {stat['missing']} |")
    (HERE / "coverage.md").write_text("\n".join(lines) + "\n", encoding="utf-8")
    log("coverage.md written")


def main() -> None:
    manifest = json.loads((RAW / "gallery_images.json").read_text())
    (CONTENT / "pages").mkdir(parents=True, exist_ok=True)

    meta = load_photographer_meta()
    photographer_stats = convert_photographers(manifest, meta)
    memoriam_missing = convert_memoriam(meta)

    convert_my_story()
    convert_page("mission", "mission", "Mission")
    convert_page("stories", "stories", "Stories of Others")
    convert_survivor_stories()
    convert_sketches()
    convert_stories()
    convert_press()
    convert_site_assets(manifest)
    write_coverage(photographer_stats, memoriam_missing)
    log("CONVERT DONE")


if __name__ == "__main__":
    main()
