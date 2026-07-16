#!/usr/bin/env python3
"""Pass the Cloudflare challenge once with a real browser and save the
clearance cookies for the plain-HTTP scraper.

Writes raw/cf_session.json: {"user_agent": ..., "cookies": {name: value}}.
fetch_live.py picks it up automatically. The clearance is tied to this
server's IP and the exact user agent, so both must stay identical.
"""
import json
import sys
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

RAW = Path(__file__).parent / "raw"
SESSION_FILE = RAW / "cf_session.json"
TARGET = "https://sniperalley.photo/photographers/"


def main() -> int:
    RAW.mkdir(exist_ok=True)
    with sync_playwright() as p:
        browser = p.chromium.launch(
            channel="chrome",
            headless=False,  # run under Xvfb; headless is detected
            args=["--disable-blink-features=AutomationControlled", "--no-first-run"],
        )
        context = browser.new_context(
            viewport={"width": 1366, "height": 864},
            locale="en-US",
            timezone_id="Europe/Sarajevo",
        )
        page = context.new_page()
        page.goto(TARGET, wait_until="domcontentloaded", timeout=60000)

        cleared = False
        for cycle in range(48):  # up to ~2 minutes
            title = page.title()
            if "Just a moment" not in title and "Attention Required" not in title:
                cleared = True
                break
            # Turnstile checkbox lives in a nested iframe; try clicking it
            if cycle % 4 == 3:
                try:
                    for frame in page.frames:
                        if "challenges.cloudflare.com" in (frame.url or ""):
                            box = frame.frame_element().bounding_box()
                            if box:
                                page.mouse.click(box["x"] + 30, box["y"] + box["height"] / 2)
                                break
                except Exception:
                    pass
            time.sleep(2.5)

        if not cleared:
            print("CHALLENGE NOT CLEARED (title: %r)" % page.title(), flush=True)
            browser.close()
            return 1

        cookies = {c["name"]: c["value"] for c in context.cookies("https://sniperalley.photo")}
        user_agent = page.evaluate("navigator.userAgent")
        browser.close()

    if "cf_clearance" not in cookies:
        print("cleared page but no cf_clearance cookie; cookies: %s" % list(cookies), flush=True)
        # some setups clear without the cookie when no challenge was applied
    SESSION_FILE.write_text(json.dumps({"user_agent": user_agent, "cookies": cookies}, indent=1))
    print("session saved: ua=%s cookies=%s" % (user_agent[:60], list(cookies)), flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
