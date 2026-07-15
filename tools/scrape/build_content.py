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
    text = text.replace("\u2014", " - ").replace("\u2013", "-")
    text = text.replace(" ", " ")
    text = re.sub(r"[ \t]+", " ", text)
    return text.strip()


def yaml_quote(value: str) -> str:
    return '"' + str(value).replace('\\', '\\\\').replace('"', '\\"') + '"'


def strip_wayback(url: str) -> str:
    match = re.search(r"https?://sniperalley\.photo\S*", url)
    return match.group(0) if match else url


def wayback_fetch(url: str, timestamp: str = "2") -> bytes | None:
    wb = f"https://web.archive.org/web/{timestamp}im_/{url}"
    for attempt in range(3):
        try:
            req = urllib.request.Request(wb, headers=UA)
            with urllib.request.urlopen(req, timeout=90) as response:
                return response.read()
        except Exception:
            continue
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
    "tom-stoddart",
    "luc-delahaye",
    "annie-leibovitz",
]


def convert_photographers(manifest: list[dict], meta: dict) -> dict:
    photographers = {}
    stats = {}
    for item in manifest:
        slug = item["gallery"]
        if slug in SKIP_GALLERIES:
            continue
        source = RAW / "gallery" / slug / item["file"]
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
        name = info.get("name") or fallback_name
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


def convert_memoriam(photographer_meta: dict) -> list[str]:
    (CONTENT / "memoriam").mkdir(parents=True, exist_ok=True)
    missing = []
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
        paragraphs = extract_paragraphs(main)
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

        cover_rel = None
        image_match = re.search(r'(?:data-src|src)="([^"]*?/wp-content/uploads/[^"]+?\.(?:jpg|jpeg|png))"', main, flags=re.I)
        if image_match:
            image_url = strip_wayback(image_match.group(1))
            data = wayback_fetch(image_url)
            if data and len(data) > 1000:
                dest = MEDIA / "memoriam" / slug / "cover.jpg"
                dest.parent.mkdir(parents=True, exist_ok=True)
                dest.write_bytes(data)
                cover_rel = "cover.jpg"

        excerpt = paragraphs[0][:200] if paragraphs else ""
        lines = ["---", f"name: {yaml_quote(name)}"]
        if born:
            lines.append(f"born: {born}")
        if died:
            lines.append(f"died: {died}")
        if cover_rel:
            lines.append(f"cover: {cover_rel}")
        if gallery_slug:
            lines.append(f"photographer: {gallery_slug}")
        if excerpt:
            lines.append(f"excerpt: {yaml_quote(excerpt)}")
        lines.append("---")
        body = "\n\n".join(paragraphs)
        (CONTENT / "memoriam" / f"{slug}.md").write_text("\n".join(lines) + "\n" + body + "\n", encoding="utf-8")
    log(f"memoriam written: {len(MEMORIAL_SLUGS) - len(missing)} (missing: {missing})")
    return missing


# ---------------------------------------------------------------------- pages

def convert_page(slug: str, out_name: str, title: str, media_subdir: str | None = None) -> bool:
    raw = read_page(slug)
    if raw is None:
        return False
    main = page_main(raw)
    paragraphs = extract_paragraphs(main)
    body_parts = []
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

    body_parts.extend(paragraphs)
    lines = ["---", f"title: {yaml_quote(title)}", "---"]
    (CONTENT / "pages" / f"{out_name}.md").write_text("\n".join(lines) + "\n" + "\n\n".join(body_parts) + "\n", encoding="utf-8")
    return True


def convert_sketches() -> None:
    raw = read_page("scatches")
    if raw is None:
        return
    main = page_main(raw)
    sections = []
    for match in re.finditer(r"<h2[^>]*>(.*?)</h2>(.*?)(?=<h2|$)", main, flags=re.S):
        heading = clean_text(re.sub(r"<[^>]+>", "", match.group(1)))
        if not heading or "SWITCH" in heading:
            continue
        paragraphs = extract_paragraphs(match.group(2))
        text = "\n\n".join(p.replace("[...]", "").replace("[…]", "").strip() for p in paragraphs[:2])
        if heading and text:
            sections.append(f"## {heading}\n\n{text}")
    if sections:
        content = "---\ntitle: \"Sketches\"\n---\n" + "\n\n".join(sections) + "\n"
        (CONTENT / "pages" / "sketches.md").write_text(content, encoding="utf-8")
        log(f"sketches written with {len(sections)} sections")


# --------------------------------------------------------------------- stories

def convert_stories() -> None:
    raw = read_page("video-en")
    if raw is None:
        log("WARNING: video-en.html not fetched, skipping stories")
        return
    video_ids = []
    for match in re.finditer(r'(?:data-src|src)="[^"]*?youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,})', raw):
        if match.group(1) not in video_ids:
            video_ids.append(match.group(1))
    (CONTENT / "stories").mkdir(parents=True, exist_ok=True)
    for index, video_id in enumerate(video_ids):
        title = f"Story Behind Photo {index + 1}"
        try:
            oembed_url = ("https://www.youtube.com/oembed?format=json&url="
                          + urllib.parse.quote(f"https://www.youtube.com/watch?v={video_id}", safe=""))
            with urllib.request.urlopen(urllib.request.Request(oembed_url, headers=UA), timeout=30) as response:
                title = clean_text(json.load(response).get("title") or title)
        except Exception:
            pass
        slug = ascii_slugify(title)[:60]
        lines = [
            "---",
            f"title: {yaml_quote(title)}",
            f"youtube: {video_id}",
            f'date: "{2021 + index}-01-01"',
        ]
        if index == 0:
            lines.append("featured: true")
        lines.append("---")
        (CONTENT / "stories" / f"{slug}.md").write_text("\n".join(lines) + "\n", encoding="utf-8")
    log(f"stories written: {len(video_ids)}")


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

    convert_page("my-story", "my-story", "My Story", media_subdir="my-story")
    convert_page("mission", "mission", "Mission")
    convert_page("stories", "stories", "Stories of Others")
    convert_sketches()
    convert_stories()
    convert_press()
    convert_site_assets(manifest)
    write_coverage(photographer_stats, memoriam_missing)
    log("CONVERT DONE")


if __name__ == "__main__":
    main()
