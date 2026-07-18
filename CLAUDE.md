# Project rules

## Writing style
- Never use the em dash character in code, comments, documents, or content files. Use a hyphen, comma, or rewrite the sentence.
- Plain, direct language in all documents.

## Git
- Never mention Claude, AI, or any tool assistance in commits, code, or docs.
- No Co-Authored-By lines.
- Commit messages: absolute minimum, lowercase, just name the completed thing (examples: "scaffold", "scraper", "photographer pages", "fix gallery lightbox").
- Do not sign commits from automation (gpg signing requires a passphrase prompt).

## Project
- HydePHP static site for sniperalley.photo (rebuild with new structure).
- Content is Markdown with frontmatter, designed for manual editing. See README.md for schemas and workflows.
- Site content sections: stories behind the photos, photographers, in memoriam, latest, plus pages (my story / mission, contact, donate).
- Images live in `_media/`. Original archive scrape tooling lives in `tools/scrape/` (not part of the site build).
- Build: `php hyde build`. Local preview: `php hyde serve`. Assets: `npm run build`.
- Deploys to GitHub Pages via Actions on push to main. Initial domain: new.sniperalley.photo, later www.sniperalley.photo.
