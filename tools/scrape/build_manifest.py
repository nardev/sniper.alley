#!/usr/bin/env python3
"""Build the gallery image download manifest from the Wayback CDX index.

For every distinct photo in wp-content/gallery/<slug>/, prefer the archived
original file; fall back to the largest archived NextGEN cache rendition.
Writes tools/scrape/raw/gallery_images.json and prints a coverage summary.
"""
import json
import re
import urllib.parse
from pathlib import Path

from cdx_enum import cdx_query, RAW

CACHE_RE = re.compile(r'/gallery/([^/]+)/cache/(.+?)-nggid\d+-ngg0dyn-(\d+)x(\d+)', re.I)
ORIG_RE = re.compile(r'/gallery/([^/]+)/([^/]+\.(?:jpg|jpeg|png|gif|webp))$', re.I)


def main() -> None:
    rows = cdx_query({
        "url": "sniperalley.photo/wp-content/gallery/*",
        "output": "json",
        "fl": "timestamp,original,statuscode,length",
        "filter": "statuscode:200",
        "collapse": "urlkey",
    })
    print(f"CDX rows: {len(rows)}", flush=True)

    galleries: dict[str, dict] = {}
    for ts, original, status, length in rows:
        path = urllib.parse.unquote(urllib.parse.urlparse(original).path)
        m = CACHE_RE.search(path)
        if m:
            slug, base, w, h = m.group(1), m.group(2), int(m.group(3)), int(m.group(4))
            entry = galleries.setdefault(slug, {}).setdefault(base, {"orig": None, "cache": None})
            if entry["cache"] is None or w * h > entry["cache"][2] * entry["cache"][3]:
                entry["cache"] = (ts, original, w, h)
            continue
        m = ORIG_RE.search(path)
        if m and "/thumbs/" not in path.lower() and "/dynamic/" not in path.lower():
            slug, base = m.group(1), m.group(2)
            entry = galleries.setdefault(slug, {}).setdefault(base, {"orig": None, "cache": None})
            if entry["orig"] is None or int(length or 0) > int(entry["orig"][2] or 0):
                entry["orig"] = (ts, original, length)

    manifest = []
    n_orig = n_cache = 0
    for slug, imgs in sorted(galleries.items()):
        for base, e in sorted(imgs.items()):
            if e["orig"]:
                ts, url, _ = e["orig"]
                kind = "original"
                n_orig += 1
            else:
                ts, url, w, h = e["cache"]
                kind = f"cache-{w}x{h}"
                n_cache += 1
            manifest.append({"gallery": slug, "file": base, "timestamp": ts,
                             "original": url, "kind": kind})

    (RAW / "gallery_images.json").write_text(json.dumps(manifest, indent=1))
    print(f"galleries: {len(galleries)}  images: {len(manifest)}  "
          f"(originals: {n_orig}, cache-fallback: {n_cache})", flush=True)
    per = sorted(((s, len(g)) for s, g in galleries.items()), key=lambda kv: -kv[1])
    print("largest galleries:", per[:8], flush=True)


if __name__ == "__main__":
    main()
