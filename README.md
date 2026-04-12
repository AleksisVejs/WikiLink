# WikiLink

Vue 3 + Vite + Tailwind Wikipedia navigation game (WikiRace-style). Uses the [Wikipedia REST and Action APIs](https://www.mediawiki.org/wiki/API:Main_page) from the browser; no backend is required.

## Local development

```bash
npm install
npm run dev
```

## Production build

```bash
npm run build
```

Output is in `dist/`. Upload **all** files from `dist/` to your hosting document root (or a subdirectory). The `public/.htaccess` file is copied into `dist/` for Apache (cPanel) so client-side routes like `/play/daily` resolve to `index.html`.

### cPanel / Apache

1. Run `npm run build`.
2. Upload the contents of `dist/` via File Manager or FTP.
3. Ensure `mod_rewrite` is enabled (standard on cPanel).
4. If the site lives in a **subfolder** (e.g. `public_html/wikilink/`), set `base` in `vite.config.js` to that path (e.g. `base: '/wikilink/'`) and rebuild so asset URLs resolve correctly.

### Daily challenge

The daily puzzle uses a **UTC calendar date** (`YYYY-MM-DD`) and a deterministic seed so everyone gets the same start/target pair for that UTC day. Pairs are chosen from Wikipedia search results; if rankings change during the day, titles can theoretically shift slightly, but selection is no longer random per visitor.

## GitHub

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/AleksisVejs/WikiLink.git
git push -u origin main
```

Use a [personal access token](https://github.com/settings/tokens) or SSH when Git prompts for credentials.
