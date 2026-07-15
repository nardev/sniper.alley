#!/usr/bin/env python3
"""Download archived HTML for all pages listed in pages.json and any
single-segment posts from html_paths.json that look like content posts.

Saves files as raw/pages/<slug>.html. Resumable.
"""
import json
import re
import time
import urllib.request
from pathlib import Path

RAW = Path(__file__).parent / "raw"
OUT = RAW / "pages"
OUT.mkdir(parents=True, exist_ok=True)

UA = {"User-Agent": "Mozilla/5.0 (compatible; sniperalley-rebuild/1.0)"}

SKIP_POST_PATTERNS = re.compile(
    r"^/(ar|bs|de|en|es|fr|it|tr)/$"
    r"|^/(category|tag|author|page|feed|comments|wp-|price|sample-page)"
    r"|-(ar|bs|de|es|fr|it|tr)/$"
)


def slug_for(path: str) -> str:
    s = path.strip("/") or "home"
    return s.replace("/", "_")


def fetch(url: str, ts: str, dest: Path) -> str:
    if dest.exists() and dest.stat().st_size > 1000:
        return "skip"
    wb = f"https://web.archive.org/web/{ts}/{url}"
    for attempt in range(4):
        try:
            req = urllib.request.Request(wb, headers=UA)
            with urllib.request.urlopen(req, timeout=90) as r:
                data = r.read()
            dest.write_bytes(data)
            return "ok"
        except Exception as e:
            err = str(e)
            time.sleep(6 * (attempt + 1))
    return f"FAIL {err}"


def main() -> None:
    jobs = []
    pages = json.loads((RAW / "pages.json").read_text())
    for p in pages:
        if p["timestamp"]:
            path = "/" + p["url"].split("/", 1)[1]
            jobs.append((p["original"], p["timestamp"], slug_for(path)))

    html_paths = json.loads((RAW / "html_paths.json").read_text())
    known = {j[2] for j in jobs}
    for path, info in sorted(html_paths.items()):
        if SKIP_POST_PATTERNS.search(path):
            continue
        s = slug_for(path)
        if s in known or s == "home":
            continue
        jobs.append((info["original"], info["timestamp"], s))

    print(f"{len(jobs)} pages to fetch", flush=True)
    ok = skip = 0
    fails = []
    for i, (url, ts, s) in enumerate(jobs, 1):
        status = fetch(url, ts, OUT / f"{s}.html")
        if status == "ok":
            ok += 1
            time.sleep(1.5)
        elif status == "skip":
            skip += 1
        else:
            fails.append({"url": url, "slug": s, "error": status})
        if i % 10 == 0 or i == len(jobs):
            print(f"pages {i}/{len(jobs)} ok={ok} skip={skip} fail={len(fails)}", flush=True)
    (RAW / "page_failures.json").write_text(json.dumps(fails, indent=1))
    print(f"PAGES DONE ok={ok} skip={skip} fail={len(fails)}", flush=True)


if __name__ == "__main__":
    main()
