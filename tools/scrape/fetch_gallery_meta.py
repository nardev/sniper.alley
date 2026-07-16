#!/usr/bin/env python3
"""Merge per-photo captions/credits from archived NextGEN gallery pages
into content/photographers/*.md.

For every gallery slug, finds the newest archived copy of
/photographers/nggallery/photographers/<slug>/, parses each photo's
data-title / data-description / alt text, and updates the matching
`photos:` entries in the photographer's Markdown file. Only caption and
credit lines are touched; every other line is preserved.

Fetched pages are cached in raw/gallery-pages/ so reruns are cheap.
"""
import re
import time
import urllib.parse
import urllib.request
from pathlib import Path

from build_content import ascii_filename, clean_text, yaml_quote, GALLERY_ALIASES

HERE = Path(__file__).parent
RAW = HERE / "raw"
CACHE = RAW / "gallery-pages"
CACHE.mkdir(parents=True, exist_ok=True)
CONTENT = HERE.parent.parent / "content" / "photographers"

UA = {"User-Agent": "Mozilla/5.0 (compatible; sniperalley-rebuild/1.0)"}


def fetch_gallery_page(slug: str) -> str | None:
    cached = CACHE / f"{slug}.html"
    if cached.exists() and cached.stat().st_size > 5000:
        return cached.read_text(encoding="utf-8", errors="replace")
    url = f"https://web.archive.org/web/2/https://sniperalley.photo/photographers/nggallery/photographers/{slug}/"
    for attempt in range(3):
        try:
            req = urllib.request.Request(url, headers=UA)
            with urllib.request.urlopen(req, timeout=90) as response:
                data = response.read()
            if len(data) > 5000:
                cached.write_bytes(data)
                return data.decode("utf-8", errors="replace")
            return None
        except urllib.error.HTTPError as error:
            if error.code == 404:
                return None
            time.sleep(6 * (attempt + 1))
        except Exception:
            time.sleep(6 * (attempt + 1))
    return None


def parse_photo_meta(raw: str) -> dict[str, dict]:
    """filename -> {caption, credit} from a gallery page."""
    meta = {}
    for tag_match in re.finditer(r"<a\s[^>]*nextgen_pro_lightbox[^>]*>", raw, flags=re.I):
        tag = tag_match.group(0)
        file_match = re.search(r'data-src="[^"]*?/wp-content/gallery/[^/"]+/([^/"]+?\.(?:jpg|jpeg|png|gif|webp))"', tag, flags=re.I) \
            or re.search(r'href="[^"]*?/wp-content/gallery/[^/"]+/([^/"]+?\.(?:jpg|jpeg|png|gif|webp))"', tag, flags=re.I)
        if not file_match:
            continue
        filename = ascii_filename(file_match.group(1))
        title_match = re.search(r'data-title="([^"]*)"', tag)
        description_match = re.search(r'data-description="([^"]*)"', tag)
        title = clean_text(title_match.group(1)) if title_match else ""
        description = clean_text(description_match.group(1)) if description_match else ""
        if filename not in meta and (title or description):
            meta[filename] = {
                "credit": title.replace("©", "(c)"),
                "caption": description,
            }
    return meta


def merge_into_markdown(path: Path, meta: dict[str, dict]) -> int:
    lines = path.read_text(encoding="utf-8").splitlines()
    out, updated = [], 0
    i = 0
    while i < len(lines):
        line = lines[i]
        out.append(line)
        match = re.match(r"^(\s*)- file: (.+)$", line)
        if match:
            indent, filename = match.group(1), match.group(2).strip()
            info = meta.get(filename)
            # collect existing caption/credit lines belonging to this entry
            existing = {}
            j = i + 1
            while j < len(lines) and re.match(rf"^{indent}\s+(caption|credit):", lines[j]):
                key = lines[j].split(":", 1)[0].strip()
                existing[key] = lines[j]
                j += 1
            if info:
                child_indent = indent + "  "
                if info["caption"]:
                    out.append(f"{child_indent}caption: {yaml_quote(info['caption'])}")
                credit = info["credit"] or ""
                if credit:
                    out.append(f"{child_indent}credit: {yaml_quote(credit)}")
                elif "credit" in existing:
                    out.append(existing["credit"])
                updated += 1
            else:
                out.extend(existing.values())
            i = j
        else:
            i += 1
    if updated:
        path.write_text("\n".join(out) + "\n", encoding="utf-8")
    return updated


def main() -> None:
    files = sorted(CONTENT.glob("*.md"))
    total_updated = no_page = 0
    for path in files:
        slug = path.stem
        # a merged page may need meta from its alias source galleries too
        slugs = [slug] + [src for src, dst in GALLERY_ALIASES.items() if dst == slug]
        meta = {}
        fetched_any = False
        for gallery_slug in slugs:
            raw = fetch_gallery_page(gallery_slug)
            if raw:
                fetched_any = True
                meta.update(parse_photo_meta(raw))
            time.sleep(1.5)
        if not fetched_any:
            no_page += 1
            print(f"{slug}: NO ARCHIVED GALLERY PAGE", flush=True)
            continue
        updated = merge_into_markdown(path, meta)
        total_updated += updated
        print(f"{slug}: {updated} photos updated ({len(meta)} meta entries)", flush=True)
    print(f"META MERGE DONE files={len(files)} photos_updated={total_updated} galleries_without_page={no_page}", flush=True)


if __name__ == "__main__":
    main()
