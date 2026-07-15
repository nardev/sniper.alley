#!/usr/bin/env python3
"""Download all manifest images from the Wayback Machine.

Resumable: skips files that already exist and are non-empty. Writes progress
lines to stdout (one per 25 images) and a final summary + failures list.
"""
import json
import time
import urllib.request
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path

RAW = Path(__file__).parent / "raw"
OUT = RAW / "gallery"
OUT.mkdir(parents=True, exist_ok=True)

WORKERS = 4
UA = {"User-Agent": "Mozilla/5.0 (compatible; sniperalley-rebuild/1.0)"}


def fetch(item: dict) -> tuple[dict, str]:
    dest = OUT / item["gallery"] / item["file"]
    if dest.exists() and dest.stat().st_size > 0:
        return item, "skip"
    dest.parent.mkdir(parents=True, exist_ok=True)
    url = f"https://web.archive.org/web/{item['timestamp']}im_/{item['original']}"
    for attempt in range(4):
        try:
            req = urllib.request.Request(url, headers=UA)
            with urllib.request.urlopen(req, timeout=90) as r:
                data = r.read()
            if len(data) < 100:
                raise ValueError(f"suspiciously small response ({len(data)}B)")
            dest.write_bytes(data)
            return item, "ok"
        except Exception as e:
            err = str(e)
            time.sleep(5 * (attempt + 1))
    return item, f"FAIL {err}"


def main() -> None:
    manifest = json.loads((RAW / "gallery_images.json").read_text())
    done = ok = skip = 0
    failures = []
    t0 = time.time()
    with ThreadPoolExecutor(max_workers=WORKERS) as pool:
        futures = [pool.submit(fetch, item) for item in manifest]
        for fut in as_completed(futures):
            item, status = fut.result()
            done += 1
            if status == "ok":
                ok += 1
            elif status == "skip":
                skip += 1
            else:
                failures.append({**item, "error": status})
            if done % 25 == 0 or done == len(manifest):
                rate = done / max(time.time() - t0, 1)
                print(f"progress {done}/{len(manifest)} ok={ok} skip={skip} "
                      f"fail={len(failures)} ({rate:.1f}/s)", flush=True)
    (RAW / "download_failures.json").write_text(json.dumps(failures, indent=1))
    print(f"DOWNLOAD DONE total={len(manifest)} ok={ok} skip={skip} fail={len(failures)}",
          flush=True)


if __name__ == "__main__":
    main()
