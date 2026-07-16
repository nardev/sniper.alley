#!/usr/bin/env python3
"""Scrape the LIVE sniperalley.photo for full-size gallery originals and
content the Wayback Machine never archived.

Requires Cloudflare to be open for this server (Bot Fight Mode off or an
IP allow rule that actually bypasses challenges). Run the probe first:

    python3 fetch_live.py probe

Then the full run (sequential, polite, resumable):

    python3 fetch_live.py all

Downloads into raw/live-gallery/<slug>/ and raw/live-pages/. The import
into _media and content happens in import_live.py.
"""
import json
import re
import sys
import time
import urllib.parse
import urllib.request
from pathlib import Path

HERE = Path(__file__).parent
RAW = HERE / "raw"
GALLERY_OUT = RAW / "live-gallery"
PAGES_OUT = RAW / "live-pages"

UA = {"User-Agent": "Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0"}
BASE = "https://sniperalley.photo"
DELAY = 0.8


def get(url: str, binary: bool = False, retries: int = 3):
    for attempt in range(retries):
        try:
            req = urllib.request.Request(url, headers=UA)
            with urllib.request.urlopen(req, timeout=120) as response:
                data = response.read()
            if b"Just a moment" in data[:2000]:
                raise RuntimeError("Cloudflare challenge")
            return data if binary else data.decode("utf-8", errors="replace")
        except urllib.error.HTTPError as error:
            if error.code == 404:
                return None
            time.sleep(5 * (attempt + 1))
        except Exception:
            time.sleep(5 * (attempt + 1))
    return None


def probe() -> bool:
    data = get(f"{BASE}/photographers/", retries=1)
    ok = data is not None and "nggallery" in data
    print("live access:", "OK" if ok else "BLOCKED", flush=True)
    return ok


def gallery_slugs() -> list[str]:
    raw = get(f"{BASE}/photographers/")
    slugs = []
    for match in re.finditer(r"/photographers/nggallery/photographers/([^\"/]+)", raw):
        slug = urllib.parse.unquote(match.group(1))
        if slug not in slugs:
            slugs.append(slug)
    (RAW / "live_gallery_slugs.json").write_text(json.dumps(slugs, indent=1))
    print(f"{len(slugs)} galleries on live site", flush=True)
    return slugs


def fetch_gallery(slug: str) -> None:
    """Fetch the gallery page (for meta + file list) and all full-size images."""
    page_dest = PAGES_OUT / f"gallery-{slug}.html"
    page = None
    if page_dest.exists() and page_dest.stat().st_size > 5000:
        page = page_dest.read_text(encoding="utf-8", errors="replace")
    else:
        page = get(f"{BASE}/photographers/nggallery/photographers/{slug}/")
        if page:
            page_dest.write_text(page, encoding="utf-8")
            time.sleep(DELAY)
    if not page:
        print(f"{slug}: GALLERY PAGE FAILED", flush=True)
        return
    files = []
    for match in re.finditer(
            r'data-src="[^"]*?/wp-content/gallery/' + re.escape(slug) + r'/([^/"]+?\.(?:jpg|jpeg|png|gif|webp))"',
            page, flags=re.I):
        name = urllib.parse.unquote(match.group(1))
        if name not in files:
            files.append(name)
    ok = skip = fail = 0
    for name in files:
        dest = GALLERY_OUT / slug / name
        if dest.exists() and dest.stat().st_size > 1000:
            skip += 1
            continue
        url = f"{BASE}/wp-content/gallery/{urllib.parse.quote(slug)}/{urllib.parse.quote(name)}"
        data = get(url, binary=True)
        if data and len(data) > 1000:
            dest.parent.mkdir(parents=True, exist_ok=True)
            dest.write_bytes(data)
            ok += 1
        else:
            fail += 1
        time.sleep(DELAY)
    print(f"{slug}: {len(files)} photos (ok={ok} skip={skip} fail={fail})", flush=True)


EXTRA_PAGES = ["emil-grebenar-en", "jordi-pujol-puente-en", "video-en", "in-memoriam"]


def fetch_extra_pages() -> None:
    for slug in EXTRA_PAGES:
        dest = PAGES_OUT / f"{slug}.html"
        if dest.exists() and dest.stat().st_size > 5000:
            continue
        page = get(f"{BASE}/{slug}/")
        if page:
            dest.write_text(page, encoding="utf-8")
            print(f"{slug}: ok", flush=True)
        else:
            print(f"{slug}: FAILED", flush=True)
        time.sleep(DELAY)


def main() -> None:
    mode = sys.argv[1] if len(sys.argv) > 1 else "all"
    if mode == "probe":
        sys.exit(0 if probe() else 1)
    if not probe():
        print("Aborting: Cloudflare still blocks this server.", flush=True)
        sys.exit(1)
    GALLERY_OUT.mkdir(parents=True, exist_ok=True)
    PAGES_OUT.mkdir(parents=True, exist_ok=True)
    fetch_extra_pages()
    for slug in gallery_slugs():
        fetch_gallery(slug)
    print("LIVE FETCH DONE", flush=True)


if __name__ == "__main__":
    main()
