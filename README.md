# CyberBlog v2.0

A lightweight, self-hosted PHP + MySQL blog CMS. No framework, no build step required to run it (only to rebuild the CSS after editing markup), and it's designed to be installed on ordinary shared/cPanel hosting as easily as on your own server.

## Features

- **Guided setup wizard** - detects a missing/unconfigured database automatically and walks you through connecting it, installing the schema, and creating your first admin account. Includes an "overwrite existing data" option for clean reinstalls.
- **Fully customizable identity** - blog name, tagline, About page, footer text, and the header's "More" menu are all editable from Settings, no code changes needed.
- **Rich post editor** - TinyMCE-based editor with image uploads, internal linking helper, related posts, and one-click YouTube video embedding.
- **SEO built in** - canonical URLs, Open Graph/Twitter meta tags, `robots.txt`, XML sitemap, JSON-LD structured data (WebSite/BlogPosting/Breadcrumb), and clean `/post/slug` URLs.
- **First-party analytics** - self-hosted pageview/visitor/referrer/device/country/search tracking with zero added page-load cost (no third-party script, no client-side beacon). Dashboard at Admin → Analytics.
- **One-click backup & restore** - downloads a `.zip` containing a full database dump plus every uploaded photo; restorable on any instance, including a fresh install.
- **Editorial team & roles** - Super Editor / Editor / Viewer roles, editorial team profiles, comment moderation.
- **Reader engagement** - Google sign-in commenting, newsletter signup, contact form, topic suggestions.
- **Fast by default** - Tailwind CSS is compiled and purged at build time (not loaded from a CDN at runtime), fonts are self-hosted, static assets are cached and gzipped.

## Requirements

- PHP 7.4+ with the `mysqli` extension
- MySQL 5.7+ or MariaDB 10.2+
- Apache with `mod_rewrite` (an `.htaccess` file is included)
- Node.js, only if you want to rebuild the CSS after changing markup/classes (`npm install && npm run build:css`)

## Installation

1. Upload the contents of this repository to your web root (or a subfolder - the app detects its own location automatically).
2. Visit the site in a browser. If the database isn't set up yet, you'll be redirected to the setup wizard automatically.
3. Follow the wizard: it checks requirements, connects to your database and installs the schema, then has you create your admin account (and optionally a TinyMCE API key - see below).
4. Log in at `/admin/login.php` and you're ready to go.

To reinstall cleanly onto a database that already has old CyberBlog tables in it, check "Overwrite existing data" on the database step.

### TinyMCE API key (optional)

The post editor uses TinyMCE Cloud's free tier. You can skip this during setup - the editor still works, it just shows a small "unregistered domain" notice. To add or change it later: **Admin → Settings → Post Editor**, or sign up free at [tiny.cloud/auth/signup](https://www.tiny.cloud/auth/signup/).

## Configuration

`config.php` holds safe defaults and is committed to the repo. Real credentials (database, Google OAuth, TinyMCE key) live in `config.local.php`, which is git-ignored and either written automatically by the setup wizard or created by copying `config.local.example.php`.

## Backup & Restore

**Admin → Backup & Restore** (Super Editor only). Downloads a `.zip` with `database.sql` plus everything in `/uploads`. Restoring accepts that same `.zip`, or a legacy `.sql`-only file for backward compatibility - it replaces all current data, so only restore a file you trust.

## Development

```bash
npm install
npm run build:css     # one-time build
npm run watch:css     # rebuild on change while editing
```

## Project Structure

```
admin/       Admin panel (posts, categories, users, settings, analytics, backup)
public/      Public-facing pages (home, post, category, search, about, contact...)
comments/    Google OAuth sign-in and comment submission
includes/    Shared PHP (db connection, helpers, auth, analytics, migrations)
install/     First-run setup wizard and database schema
assets/      Compiled CSS, self-hosted fonts, JS
uploads/     User-uploaded images
```

## License

All rights reserved.
