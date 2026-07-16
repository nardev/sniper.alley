# Scrape coverage report

Updated after the live-site full-size import (Track B). Photographs are now
the current full-resolution images from sniperalley.photo (Cloudflare was
opened for this server; a headless browser solved the challenge and cookies
were reused for a plain-HTTP scrape). Images are re-encoded to JPEG quality
82, max 1600px longest edge, to keep the site within GitHub Pages limits.

- Photographer galleries: 132
- Photos: 2819 (full-size from the live site)
- Photographers media size: 612 MB
- Every photo carries the copyright credit parsed from the live gallery page.
- Two galleries recovered that were never archived: Massimo D'Amato, Mohsen Rastani.
- Emil Grebenar In Memoriam page built from the live site (never archived).
- derek-hudson: kept at archived resolution (gallery no longer on the live site).

Full-size originals live under _media/photographers/<slug>/. To replace any
image, drop a file with the same name; the content file references it by name.
