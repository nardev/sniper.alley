#!/usr/bin/env python3
"""Enumerate archived sniperalley.photo assets via the Wayback Machine CDX API.

Produces JSON manifests under tools/scrape/raw/:
  - gallery_images.json: best capture per original gallery image (cache/thumbs excluded)
  - pages.json: latest capture per HTML page of interest
"""
import json
import sys
import time
import urllib.parse
import urllib.request
from pathlib import Path

RAW = Path(__file__).parent / "raw"
RAW.mkdir(exist_ok=True)

CDX = "https://web.archive.org/cdx/search/cdx"


def cdx_query(params: dict, retries: int = 5) -> list[list[str]]:
    qs = urllib.parse.urlencode(params, doseq=True)
    url = f"{CDX}?{qs}"
    for attempt in range(retries):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "sniperalley-rebuild/1.0"})
            with urllib.request.urlopen(req, timeout=120) as r:
                data = json.load(r)
            return data[1:] if data else []  # first row is the header
        except Exception as e:
            wait = 10 * (attempt + 1)
            print(f"  CDX retry {attempt+1}/{retries} after error: {e} (sleep {wait}s)", flush=True)
            time.sleep(wait)
    raise RuntimeError(f"CDX query failed permanently: {url}")


def enum_gallery_images() -> None:
    print("Enumerating wp-content/gallery/* images ...", flush=True)
    rows = cdx_query({
        "url": "sniperalley.photo/wp-content/gallery/*",
        "output": "json",
        "fl": "timestamp,original,mimetype,statuscode,length",
        "filter": "statuscode:200",
        "collapse": "urlkey",
    })
    keep, skipped = {}, 0
    for ts, original, mime, status, length in rows:
        path = urllib.parse.urlparse(original).path
        low = path.lower()
        if "/cache/" in low or "/thumbs/" in low or "/dynamic/" in low:
            skipped += 1
            continue
        if not low.endswith((".jpg", ".jpeg", ".png", ".gif", ".webp")):
            skipped += 1
            continue
        # key by path so http/https duplicates collapse; keep the largest capture
        prev = keep.get(path)
        if prev is None or int(length or 0) > int(prev["length"] or 0):
            keep[path] = {"timestamp": ts, "original": original, "length": length}
    out = sorted(keep.values(), key=lambda r: r["original"])
    (RAW / "gallery_images.json").write_text(json.dumps(out, indent=1))
    print(f"  {len(out)} original images kept ({skipped} cache/thumb rows skipped)", flush=True)


PAGE_URLS = [
    "sniperalley.photo/",
    "sniperalley.photo/my-story/",
    "sniperalley.photo/mission/",
    "sniperalley.photo/photographers/",
    "sniperalley.photo/in-memoriam/",
    "sniperalley.photo/video-en/",
    "sniperalley.photo/stories/",
    "sniperalley.photo/adnans-story/",
    "sniperalley.photo/scatches/",
    "sniperalley.photo/contact-us/",
    "sniperalley.photo/press/",
    "sniperalley.photo/karsten-thielker-en/",
    "sniperalley.photo/yannis-behrakis-en/",
    "sniperalley.photo/romano-cagnoni-en/",
    "sniperalley.photo/abbas-attar-en/",
    "sniperalley.photo/emil-grebenar-en/",
    "sniperalley.photo/hidajet-delic-en/",
    "sniperalley.photo/gerard-rondeau-en/",
    "sniperalley.photo/anja-niedringhaus-en/",
    "sniperalley.photo/danilo-krstanovic-en/",
    "sniperalley.photo/paul-marchand-en/",
    "sniperalley.photo/alexandra-boulat-en/",
    "sniperalley.photo/kurt-schork-en/",
    "sniperalley.photo/salko-hondo-en/",
    "sniperalley.photo/jordi-pujol-puente-en/",
]


def enum_pages() -> None:
    print("Enumerating page snapshots ...", flush=True)
    out_file = RAW / "pages.json"
    pages = json.loads(out_file.read_text()) if out_file.exists() else []
    done = {p["url"] for p in pages}
    for url in PAGE_URLS:
        if url in done:
            continue
        rows = cdx_query({
            "url": url,
            "output": "json",
            "fl": "timestamp,original,statuscode",
            "filter": "statuscode:200",
            "limit": "-3",  # last 3 captures
        })
        if rows:
            ts, original, _ = rows[-1]
            pages.append({"url": url, "timestamp": ts, "original": original,
                          "fallbacks": [r[0] for r in rows[:-1]]})
            print(f"  {url} -> {ts}", flush=True)
        else:
            pages.append({"url": url, "timestamp": None, "original": None, "fallbacks": []})
            print(f"  {url} -> NO SNAPSHOT", flush=True)
        out_file.write_text(json.dumps(pages, indent=1))
        time.sleep(2)


def enum_sketch_and_story_posts() -> None:
    """Find individual sketch/story post URLs archived at the domain root."""
    print("Enumerating all archived HTML paths (for sketch/story posts) ...", flush=True)
    rows = cdx_query({
        "url": "sniperalley.photo/*",
        "output": "json",
        "fl": "timestamp,original,mimetype,statuscode",
        "filter": ["statuscode:200", "mimetype:text/html"],
        "collapse": "urlkey",
    })
    paths = {}
    for ts, original, mime, status in rows:
        p = urllib.parse.urlparse(original).path
        if p.count("/") == 2 and p.endswith("/"):
            paths.setdefault(p, {"timestamp": ts, "original": original})
    (RAW / "html_paths.json").write_text(json.dumps(paths, indent=1))
    print(f"  {len(paths)} single-segment HTML paths archived", flush=True)


if __name__ == "__main__":
    which = sys.argv[1] if len(sys.argv) > 1 else "all"
    if which in ("all", "gallery"):
        enum_gallery_images()
    if which in ("all", "pages"):
        enum_pages()
    if which in ("all", "posts"):
        enum_sketch_and_story_posts()
    print("Enumeration DONE", flush=True)
