# WikiLink

WikiLink is a Wikipedia navigation game (WikiRace-style) built with Vue 3, Vite, and Tailwind CSS, with a PHP API for multiplayer, lobby, match flow, auth, and related game services.

## Live Website

Production is hosted at [https://wikilink.fraksis.com/](https://wikilink.fraksis.com/).

## Tech Stack

- Frontend: Vue 3 + Vue Router + Vite + Tailwind CSS
- Backend/API: PHP endpoints in `api/`
- Data: SQLite (for API-side persistence)
- Hosting: Apache/cPanel

## Project Structure

- `src/` - Vue application (views, composables, router, components)
- `api/` - PHP API endpoints and backend logic
- `public/` - static files and Apache rewrite config for SPA routing
- `dist/` - production build output (generated)

## Local Development

### 1) Install frontend dependencies

```bash
npm install
```

### 2) Run frontend (Vite)

```bash
npm run dev
```

### 3) Run API locally (optional, for multiplayer/backend features)

From the `api/` folder:

```bash
php -S localhost:8080 router.php
```

This uses `api/router.php` as the local front controller.

## Build for Production

```bash
npm run build
```

Build output is generated in `dist/`.

## Deployment (cPanel / Apache)

1. Run `npm run build`.
2. Upload all contents of `dist/` to the target web root (or subdirectory).
3. Ensure Apache `mod_rewrite` is enabled.
4. If deploying in a subfolder, set the correct Vite `base` in `vite.config.js` (for example `'/wikilink/'`) and rebuild.
5. Deploy the `api/` folder to a PHP-enabled path on the same host.

## Notes

- The app includes both single-player and backend-powered multiplayer/game services.
- Daily challenge behavior is deterministic by UTC day.
- If API routes are moved, update frontend API base references accordingly.
