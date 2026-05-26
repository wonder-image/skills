# Wonder Site Project Structure

Verified against `wonder-image/new-site` (https://github.com/wonder-image/new-site.git).

## Ownership Model

- The site project is the real runtime.
- The framework lives under `vendor/wonder-image/app` (Composer).
- The JS / CSS design system is copied under `assets/lib/wonder-image/dist/` by `npm install` from `wonder-image/lib` (npm).
- Default editing targets are `custom/`, `app/`, `assets/{ASSETS_VERSION}/`, and `lang/`.
- Treat framework code and copied lib assets as upstream. Do not patch `vendor/` or `assets/lib/wonder-image/dist/` directly.

## Main Areas

- `custom/` — site-specific code that overrides or extends the framework
  - `custom/config/` — project config: `config.php`, `lang.php`, `navigation.php`, `permissions.php`
  - `custom/function/function.php` — project-level helper functions
  - `custom/routes/` — project-specific route files
  - `custom/http/` — request handlers wired from custom routes. Canonical pattern: `Route::get(path, $ROOT.'/custom/http/<area>/<handler>.php')`, mirroring the framework's `$ROOT_APP.'/http/<area>/...'`. Documented in `vendor/wonder-image/app/docs/app/app/routing.md`. The scaffold ships `custom/http/` empty because the default frontend routes dispatch directly to view pages (`$ROOT.'/custom/view/pages/frontend/<page>.php'`); use `custom/http/` once a route needs actual handler logic (auth, redirects, dynamic dispatch, `mask()`, etc.)
  - `custom/view/pages/{frontend,backend}/...` — project pages (e.g. `pages/frontend/home.php`, `pages/frontend/contact.php`)
  - `custom/view/components/{frontend,backend}/...` — component overrides mirroring the framework component tree
  - `custom/view/layout/{frontend,backend}/...` — **layout bases** for pages. A layout file wraps a page in `<html><head><body>` (or a backend chrome), nests itself inside a base via `\Wonder\View\View::layout('frontend.base')`, and exposes `$PAGE_CONTENT` as the page-content placeholder. The framework ships `app/view/layout/frontend/base.php` and `app/view/layout/backend/{base,auth,main,form,list,show}.php` — for example, `backend/auth.php` is a minimal centered wrapper (no header / footer) used by login pages, while `backend/main.php` includes the standard `backend.layout.header` + `backend.layout.footer`. Add a site-specific layout (e.g. an "auth header minimal + footer minimal" for a public auth flow) by creating `custom/view/layout/frontend/auth-minimal.php` that calls `\Wonder\View\View::layout('frontend.base')`, wraps `$PAGE_CONTENT` with the minimal header / footer components, and closes with `\Wonder\View\View::end()`.
- `app/` — PSR-4 `App\\` classes
  - `app/Models/` — site Models (namespace `App\Models`)
  - `app/Resources/` — site Resources (namespace `App\Resources`)
- `assets/` — public frontend assets
  - `assets/{ASSETS_VERSION}/css/set-up/color.css` — color tokens (read by backend through the framework)
  - `assets/{ASSETS_VERSION}/css/set-up/root.css` — root-level CSS variables
  - `assets/{ASSETS_VERSION}/css/` — custom CSS files (outside `set-up/`)
  - `assets/{ASSETS_VERSION}/js/` — custom JS files
  - `assets/{ASSETS_VERSION}/icons/`, `assets/{ASSETS_VERSION}/images/` — site icons and images
  - `assets/lib/wonder-image/dist/` — copied from npm; `backend/`, `frontend/`, `lib/`, `fonts/`, `images/`. **Not hand-edited.**
  - `assets/temp/`, `assets/upload/` — runtime upload destinations
  - `{ASSETS_VERSION}` is discovered dynamically — `0.0/` in the current new-site, but do not hardcode
- `lang/` — locale dictionaries
  - `lang/{locale}/*.json` — any number of JSON files. The site ships `pages.json`, `components.json`, `emails.json`, `legal.json` by default, and additional files (`terms.json`, `example.json`, ...) are picked up automatically without any registration step.
  - Each file is a flat JSON dictionary keyed by dot-path keys, read by `__t("file.path.to.key")` (the leading segment matches the filename).
  - Default locales in new-site: `it`, `en`. Add a locale by creating `lang/<code>/...` and registering it in `custom/config/lang.php`.
- Project entry points (Forge-managed): `handler/`, `api/`, `backend/`, `contact/`, `legal/`
- `vendor/` — Composer, includes `vendor/wonder-image/app/` (the framework). **Read-only for site work.**
- `node_modules/` — npm, includes `node_modules/wonder-image/` (the lib package). **Read-only.**

## Where New Work Usually Goes

- Pages → `custom/view/pages/{frontend,backend}/...`
- Component overrides → `custom/view/components/{frontend,backend}/...` (mirror the framework path)
- Layout bases (page wrappers like auth-minimal, app-shell, etc.) → `custom/view/layout/{frontend,backend}/...`
- Routes → `custom/routes/`
- Project config → `custom/config/{config,lang,navigation,permissions}.php`
- Models / Resources → `app/Models/`, `app/Resources/` with namespace `App\\`
- Site assets → `assets/{ASSETS_VERSION}/css/`, `assets/{ASSETS_VERSION}/js/`, `assets/{ASSETS_VERSION}/icons/`, `assets/{ASSETS_VERSION}/images/` (never under `set-up/` unless touching token files)
- Translations → `lang/{locale}/*.json` (any JSON file in the locale directory)

## Generated or Protected Areas

- **Never edit** `vendor/`, `node_modules/`, `assets/lib/wonder-image/dist/`.
- Do not hand-edit:
  - Forge-generated entry points: `handler/index.php`, `.htaccess`, `robots.txt`
  - Runtime upload destinations under `assets/upload/`
  - Runtime sitemap output under `shared/sitemap/`
  - Compiled / merged outputs that Forge regenerates on `php forge update --local`
- `assets/{ASSETS_VERSION}/css/set-up/{color,root}.css` are NOT generated — they are the design-system token files for the site. They are read by the backend through the framework. Edit them only when intentionally changing tokens, and do not place custom (non-token) CSS inside `set-up/`.

## Framework Boundary Clues

- If the required change needs edits under `vendor/wonder-image/app/`, switch to **wi-app** — that is framework work.
- If the change is specific to one site (one page, one resource, one route, one language file), keep it in the site project.
- If the feature should be reusable across multiple sites, prefer an external Composer module package `wonder-image/<slug>` enabled via `custom/config/modules.php`, instead of embedding it directly.
- If the change is to the JS / CSS design system itself, switch to `wonder-image/lib` (https://github.com/wonder-image/lib.git) — different repo.
