#!/usr/bin/env python3
"""Import full-size photos and captions scraped from the live site into the
content tree, replacing the low-res archive imports.

For each live gallery:
  - map it to our canonical photographer slug (live slugs carry NextGEN
    re-upload suffixes like -7 and added life-date suffixes; ours are clean),
  - copy the full-size originals into _media/photographers/<slug>/ (replacing
    the archive versions), downscaling anything wider than MAX_EDGE,
  - rewrite the file's `photos:` list from the real files, with captions and
    credits parsed from the live gallery page,
  - preserve every other frontmatter field and the body (bios etc).

New galleries with no existing file (massimo-damato, mohsen-rastani) get a
fresh content file. Existing frontmatter always wins for name/born/died so
the hand-fixed spellings and dates are kept.
"""
import json
import re
import shutil
import unicodedata
import urllib.parse
from pathlib import Path

from PIL import Image

from build_content import ascii_filename, clean_text, yaml_quote, NAME_OVERRIDES

HERE = Path(__file__).parent
RAW = HERE / "raw"
LIVE_GALLERY = RAW / "live-gallery"
LIVE_PAGES = RAW / "live-pages"
ROOT = HERE.parent.parent
MEDIA = ROOT / "_media" / "photographers"
CONTENT = ROOT / "content" / "photographers"

MAX_EDGE = 1600          # downscale longest side above this
JPEG_QUALITY = 82


def norm_key(slug: str) -> str:
    """Comparable key: drop NextGEN -<n> suffix and -YYYY-YYYY life dates."""
    s = re.sub(r"-\d{4}-\d{4}$", "", slug)
    s = re.sub(r"-\d+$", "", s)
    spelling = {
        "raffaele-ciriello": "raffaelle-ciriello",
        "wolfgang-bellwinkel": "wolfgang-bellwinkle",
        "zoran-filipovic-zoro": "zoran-filipovic",
    }
    return spelling.get(s, s)


def existing_slug_map() -> dict[str, str]:
    """norm_key -> our content slug, for files already in the tree."""
    mapping = {}
    for path in CONTENT.glob("*.md"):
        mapping[norm_key(path.stem)] = path.stem
    return mapping


def parse_live_album() -> dict[str, dict]:
    page = (LIVE_PAGES / "photographers.html").read_text(encoding="utf-8", errors="replace")
    meta = {}
    for tile in re.split(r"<div class=['\"]image_container['\"]>", page)[1:]:
        sm = re.search(r"/photographers/nggallery/photographers/([a-z0-9][a-z0-9-]*)", tile)
        if not sm:
            continue
        slug = sm.group(1)
        cm = re.search(r"caption_link['\"]><a[^>]*>\s*(.*?)\s*</a>", tile, re.S)
        caption = clean_text(re.sub(r"<[^>]+>", "", cm.group(1))) if cm else ""
        name, born, died = caption, None, None
        dm = re.search(r"\((\d{4})\s*-\s*(\d{4})\)", caption)
        if dm:
            born, died = int(dm.group(1)), int(dm.group(2))
            name = caption[: dm.start()].strip()
        meta[slug] = {"name": name, "born": born, "died": died}
    return meta


def parse_live_captions(slug: str) -> dict[str, dict]:
    """filename -> {caption, credit} from a live gallery page."""
    page_path = LIVE_PAGES / f"gallery-{slug}.html"
    if not page_path.exists():
        return {}
    raw = page_path.read_text(encoding="utf-8", errors="replace")
    meta = {}
    for tag in re.finditer(r"<a\s[^>]*nextgen_pro_lightbox[^>]*>", raw, re.I):
        block = tag.group(0)
        fm = re.search(r"/wp-content/gallery/[^/'\"]+/([^/'\"?]+?\.(?:jpg|jpeg|png|gif|webp))", block, re.I)
        if not fm:
            continue
        filename = ascii_filename(fm.group(1))
        title = re.search(r'data-title="([^"]*)"', block)
        desc = re.search(r'data-description="([^"]*)"', block)
        credit = clean_text(title.group(1)) if title else ""
        caption = clean_text(desc.group(1)) if desc else ""
        if filename not in meta:
            meta[filename] = {"credit": credit.replace("©", "(c)"), "caption": caption}
    return meta


def copy_full_size(live_slug: str, dest_slug: str) -> list[str]:
    src_dir = LIVE_GALLERY / live_slug
    if not src_dir.is_dir():
        return []
    dest_dir = MEDIA / dest_slug
    if dest_dir.exists():
        shutil.rmtree(dest_dir)
    dest_dir.mkdir(parents=True, exist_ok=True)
    names = []
    for src in sorted(src_dir.iterdir()):
        if not src.is_file() or src.stat().st_size < 1000:
            continue
        name = ascii_filename(src.name)
        dest = dest_dir / name
        try:
            with Image.open(src) as im:
                im.load()
                if max(im.size) > MAX_EDGE:
                    ratio = MAX_EDGE / max(im.size)
                    im = im.resize((round(im.width * ratio), round(im.height * ratio)))
                if name.lower().endswith((".jpg", ".jpeg")):
                    if im.mode not in ("RGB", "L"):
                        im = im.convert("RGB")
                    im.save(dest, "JPEG", quality=JPEG_QUALITY, optimize=True, progressive=True)
                else:
                    im.save(dest)
        except Exception:
            shutil.copy2(src, dest)
        names.append(name)
    return names


def frontmatter_and_body(path: Path) -> tuple[dict, str]:
    text = path.read_text(encoding="utf-8")
    m = re.match(r"^---\n(.*?)\n---\n?(.*)$", text, re.S)
    if not m:
        return {}, text
    import yaml
    return yaml.safe_load(m.group(1)) or {}, m.group(2)


def write_photographer(slug: str, meta: dict, body: str, photos: list[dict],
                       existing: dict | None) -> None:
    existing = existing or {}
    name = existing.get("name") or NAME_OVERRIDES.get(slug) or meta.get("name") or slug.replace("-", " ").title()
    born = existing.get("born") or meta.get("born")
    died = existing.get("died") or meta.get("died")
    portrait = existing.get("portrait")
    if portrait not in {p["file"] for p in photos}:
        portrait = photos[0]["file"] if photos else None

    lines = ["---", f"name: {yaml_quote(name)}"]
    if born:
        lines.append(f"born: {born}")
    if died:
        lines.append(f"died: {died}")
    lines.append(f"role: {existing.get('role', 'Photojournalist')}")
    if existing.get("country"):
        lines.append(f"country: {yaml_quote(existing['country'])}")
    if existing.get("blurb"):
        lines.append(f"blurb: {yaml_quote(existing['blurb'])}")
    if portrait:
        lines.append(f"portrait: {portrait}")
    if existing.get("featured"):
        lines.append("featured: true")
    if existing.get("memoriam"):
        lines.append(f"memoriam: {existing['memoriam']}")
    if existing.get("quote"):
        lines.append(f"quote: {yaml_quote(existing['quote'])}")
    lines.append("photos:")
    for photo in photos:
        lines.append(f"  - file: {photo['file']}")
        if photo.get("caption"):
            lines.append(f"    caption: {yaml_quote(photo['caption'])}")
        if photo.get("credit"):
            lines.append(f"    credit: {yaml_quote(photo['credit'])}")
    lines.append("---")
    (CONTENT / f"{slug}.md").write_text("\n".join(lines) + "\n" + (body.strip() + "\n" if body.strip() else ""), encoding="utf-8")


def main() -> None:
    album = parse_live_album()
    existing_map = existing_slug_map()
    new_slugs = {"massimo-damato", "mohsen-rastani"}

    imported = new = 0
    for live_slug in sorted(album):
        key = norm_key(live_slug)
        dest_slug = existing_map.get(key, live_slug if live_slug in new_slugs else None)
        if dest_slug is None:
            # a live gallery we could not map and did not expect; skip loudly
            print(f"UNMAPPED live gallery: {live_slug}", flush=True)
            continue
        files = copy_full_size(live_slug, dest_slug)
        if not files:
            continue
        captions = parse_live_captions(live_slug)
        photos = [{"file": f, **captions.get(f, {})} for f in files]

        existing_path = CONTENT / f"{dest_slug}.md"
        existing_fm, body = frontmatter_and_body(existing_path) if existing_path.exists() else ({}, "")
        if not existing_path.exists():
            new += 1
        write_photographer(dest_slug, album.get(live_slug, {}), body, photos, existing_fm)
        imported += 1
        print(f"{live_slug} -> {dest_slug}: {len(files)} full-size photos", flush=True)

    print(f"IMPORT DONE galleries={imported} new={new}", flush=True)


if __name__ == "__main__":
    main()
