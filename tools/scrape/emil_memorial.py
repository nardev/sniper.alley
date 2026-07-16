#!/usr/bin/env python3
"""Build the Emil Grebenar In Memoriam page from the live site.

Emil's tribute was never archived, so its text and images come from the
live page (raw/live-pages/emil-grebenar-en.html) with images fetched live.
Produces content/memoriam/emil-grebenar.md and _media/memoriam/emil-grebenar/.
"""
import re
from pathlib import Path

import build_content as bc
import fetch_live as fl

HERE = Path(__file__).parent
LIVE_PAGES = HERE / "raw" / "live-pages"
MEDIA = HERE.parent.parent / "_media" / "memoriam" / "emil-grebenar"
CONTENT = HERE.parent.parent / "content" / "memoriam"

SLUG = "emil-grebenar"
PHOTOGRAPHER = "emil-grebenar-1956-2017"


def download(url: str, filename: str) -> str | None:
    dest = MEDIA / filename
    if dest.exists() and dest.stat().st_size > 1000:
        return filename
    data = fl.get(url, binary=True)
    if data and len(data) > 1000:
        dest.parent.mkdir(parents=True, exist_ok=True)
        dest.write_bytes(data)
        return filename
    return None


def main() -> None:
    raw = (LIVE_PAGES / "emil-grebenar-en.html").read_text(encoding="utf-8", errors="replace")
    title_match = re.search(r"<title[^>]*>(.*?)</title>", raw, re.S)
    name = bc.clean_text(title_match.group(1)) if title_match else "Emil Grebenar"
    name = re.split(r"\s*[|\-]\s*Sniper Alley", name)[0]
    name = re.sub(r"\s+en\s*$", "", name)
    name = re.sub(r"\s*\([^)]*\)\s*$", "", name).strip()

    sections = bc.parse_tribute_sections(raw)
    paragraphs = [p for s in sections for p in s["paragraphs"]]
    born = died = None
    joined = " ".join(paragraphs[:4])
    dm = re.search(r"(\d{4})\s*[-–]\s*(\d{4})", joined)
    if not dm:
        born_m = re.search(r"born on \w+ \d+,\s*(\d{4})", joined)
        died_m = re.search(r"(?:died|passed away)[^.]*?(\d{4})", joined)
        born = int(born_m.group(1)) if born_m else 1956
        died = int(died_m.group(1)) if died_m else 2017
    else:
        born, died = int(dm.group(1)), int(dm.group(2))

    section_images = [s["image"] for s in sections if s["image"]]
    banner_rel = None
    if section_images:
        banner_rel = download(section_images[0], bc.ascii_filename(section_images[0].rsplit("/", 1)[-1]))

    body_parts = []
    first_used = False
    for section in sections:
        if section["image"]:
            if not first_used:
                first_used = True
            else:
                fn = download(section["image"], bc.ascii_filename(section["image"].rsplit("/", 1)[-1]))
                if fn:
                    body_parts.append(f"![{name}](/media/memoriam/{SLUG}/{fn})")
        body_parts.extend(section["paragraphs"])

    excerpt = next((p for p in paragraphs if len(p) > 40), "")[:200]
    lines = ["---", f"name: {bc.yaml_quote(name)}", f"born: {born}", f"died: {died}"]
    if banner_rel:
        lines.append(f"banner: {banner_rel}")
        lines.append(f"cover: {banner_rel}")
    lines.append(f"photographer: {PHOTOGRAPHER}")
    if excerpt:
        lines.append(f"excerpt: {bc.yaml_quote(excerpt)}")
    lines.append("---")
    (CONTENT / f"{SLUG}.md").write_text("\n".join(lines) + "\n" + "\n\n".join(body_parts) + "\n", encoding="utf-8")
    print(f"emil-grebenar written: born={born} died={died} images={len(section_images)} banner={banner_rel}", flush=True)


if __name__ == "__main__":
    main()
