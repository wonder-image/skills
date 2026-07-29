# Wonder Image App Architecture

## Package Boundary

- `wonder-image/app` is a library package with Composer autoload `Wonder\\ => class/`.
- The real application runtime lives in a **site** such as `wonder-image/new-site` (see glossary in [`SKILL.md`](../SKILL.md#glossary)).
- Sites install this package under `vendor/wonder-image/app` and execute `php forge ...` from the site root.
- When a task mentions runtime behavior, always ask whether the source of truth is the framework or the site integration point.

## Bootstrap Flow

- `wonder-image.php` is the package entrypoint.
- It resolves `ROOT` by checking the existing global root, the current working directory, then paths that contain `vendor/autoload.php`.
- It loads the site autoloader, captures legacy globals, then loads:
  - `app/function/function.php`
  - `app/config/config.php`
  - `app/service/service.php`
  - `app/middleware/middleware.php`
  - `app/bootstrap/backend.php` or `app/bootstrap/frontend.php` when enabled
- Anything that changes root resolution, early globals, config load order, services, or middleware is bootstrap-sensitive.

## Environment Resolution

- `Credentials::loadEnv()` must resolve `.env` from the site `ROOT`, not from the framework directory inside `vendor/`.
- `Credentials::envRoot()` checks legacy global `ROOT`, then current working directory, then falls back to the package-adjacent root.
- DB credentials can be partially absent during first-time setup; the code intentionally tolerates this until a real DB connection is required.

## Architecture Split

- Legacy runtime still exists under `app/`.
- New architecture lives mostly under `class/App/*`.
- Prefer the newer class-based architecture for new work, but inspect both sides when following a real runtime flow.

## Media Element and Theme Contract

- Framework media Elements (`Image`, `Video`, `Iframe`, `Gallery`, `Swiper`, `GoogleMap`) extend `class/Elements/Media/Media.php`; keep shared fluent capabilities such as `CanSpanColumn` on that base rather than duplicating them per media.
- Theme media renderers extend `class/Themes/{Wonder,Bootstrap}/Media/Media.php` and implement the inner `renderMedia()` fragment. The base renderer owns layout wrapping.
- A media column wrapper is strictly opt-in: without an explicit `columnSpan()` call, rendering must return the original fragment without any additional container. With an explicit span, wrap the complete fragment, including Video filters and Gallery/Swiper auxiliary markup and scripts.
- Resolve span classes against the active design system: Wonder uses the available `col-*`, `col-t-*`, and `col-p-*` grid utilities; Bootstrap uses the available backend `col-span-*` utility. Do not emit invented breakpoint classes or add generic positioning/overflow styles to the wrapper.
- Treat `Swiper::images()` and `Swiper::slides()` as explicit, alternative content modes. `images()` keeps responsive images, thumbnails, zoom, and lightbox; `slides()` owns the `.swiper-slide` wrappers around trusted HTML or renderable objects and ignores image-only features. Render object slides with the explicitly requested theme, keep generic Bootstrap slides free of image ratio/absolute wrappers, and configure responsive behavior through the fluent `breakpoints()`, `autoHeight()`, `keyboard()`, and `watchOverflow()` APIs rather than site-specific initialization scripts. Apply explicit image/thumbnail ratios to each slide or Panzoom viewport with validated native `aspect-ratio`, not to the carousel root, so multi-slide layouts remain correct. Preserve the legacy Bootstrap 16:9 root when no ratio is configured. Normalize, deduplicate, and escape `slideClass()` / `thumbSlideClass()` values while always retaining `.swiper-slide`.
- For Bootstrap utilities that must own their direct media child (for example `ratio ratio-16x9` around an `Iframe`), use `Elements/Components/Container::noGrid()`. The Resource layout may keep the Container's outer `col-*`, but it must not add an inner `row`/gutter or wrap unspanned media children. When that Container is the root passed to `ResourceFormLayoutRenderer::renderLayout()`, render the same pure inner node directly instead of replacing it with a generated `row`. Preserve Container classes, id, style, data/aria, and boolean attributes by sharing the Bootstrap Container's inner-rendering path rather than rebuilding its markup in `ResourceFormLayoutRenderer`.
- Use `Elements/Media/GoogleMap` as the HTML-free PHP boundary for the `requireGoogleMaps()`, `MapManager`, and optional `MapNavigator` globals supplied by `wonder-image/lib`. The shared renderer concern owns credential fallback, protected JSON, one loader promise, per-element instances, explicit sizing, lifecycle events, and fallback to a classic marker when no Google Map ID exists. Pass custom marker builders only as validated global function paths; sites and modules must build feed-derived nodes with safe DOM APIs. Keep GPS navigation opt-in and do not auto-start it without an explicit caller choice.

## Backend Value Card Contract

- Keep `Elements/Components/Card` as the generic compositional container. Do not add title/value/unit/trend state to it.
- Use `InfoCard` for descriptive label/value pairs and `MetricCard` for numeric KPIs with an optional comparison. Both share the HTML-free `Elements/Components/AbstractValueCard` configuration base and Bootstrap renderers derived from `Themes/Bootstrap/Components/AbstractValueCard`.
- Only `null` and empty strings are missing values; preserve numeric zero. Escape titles, values, units, previous values, and custom attributes in the renderer.
- Metric comparisons use `(current - previous) / abs(previous) * 100`. Never divide by zero: an all-zero comparison is `0%`, while a non-zero current value against a zero baseline has an undefined percentage. Direction controls the arrow; `higherIsBetter()` / `lowerIsBetter()` controls only success versus danger color.
- In `ResourceFormLayoutRenderer`, render value cards with one native outer `col-*` derived from the parent `columns()` and call the Bootstrap renderer's inner-card path so `col-span-*` is not duplicated. Direct Bootstrap rendering applies `col-span-*` to the card node itself and does not add another wrapper. Because this is a backend renderer, resolve every framework Element child with the explicit Bootstrap theme instead of depending on the global theme.

## Shared Accordion Contract

- Keep Accordion state in the HTML-free `Elements/Components/Accordion` Element and provide a renderer in each supported theme. The Wonder renderer must compose the existing lib contract (`wi-dropdown-box`, direct `wi-dropdown-title wi-switcher`, direct `wi-dropdown-content`) instead of adding framework CSS or JavaScript.
- `expanded(true)` is server-rendered state: add `wi-show` to the Wonder box and emit the matching expanded icon. Keep icon styles semantic (`plus`, `chevron`, `plus-lg`) and map them only to icon pairs already handled by the lib.
- Restrict `titleSize()` and `descriptionSize()` to the lib typography presets (`title-big`, `title`, `subtitle`, `text`, `text-small`). Escape simple descriptions by default; use `components()` for structured content and propagate the explicitly requested theme to those child Elements.

## Model and Resource Roles

- `class/App/Model.php` owns table metadata, data schema, query helpers, and field-to-SQL conversion.
- `class/App/Resource.php` owns backend/API module behavior, form fields, query schema, permission schema, navigation schema, and repeater relation handling.
- `class/App/PageSchema/*` provides special backend pages outside standard CRUD.
- `class/App/Support/Repeater.php` plus `RepeaterColumn` and `RepeaterRelation` drive repeatable rows and related-record sync.

## Discovery and Registry Precedence

- `ModelRegistry` loads models from:
  - framework core `class/App/Models`
  - enabled module model directories
  - site `app/Models`
  - site `custom/class/Models`
- `ResourceRegistry` loads resources from:
  - framework core `class/App/Resources`
  - enabled module resource directories
  - site `app/Resources`
  - site `custom/class/Resources`
  - configured resource lists from config files when present
- Resource priority matters. Site and custom resources can override lower-priority definitions.

## Generated Routing

- Core route entry files are:
  - `app/config/routes/route.frontend.php`
  - `app/config/routes/route.backend.php`
  - `app/config/routes/route.api.php`
- Canonical package API handlers live under `app/http/api/*`; the old `app/api/*` tree has been removed and must not be recreated.
- `ResourceRouteRegistrar` generates backend and API CRUD routes from each registered resource.
- `Resource::path()` or model folder drives the backend path; the slugified path drives route names and API segments.
- Custom resource pages and permissions alter which routes are emitted.

## Module System

- Canonical modules are standalone Composer packages named `wonder-image/<slug>`.
- The standard namespace base is `Wonder\\Plugin\\<StudlySlug>\\`.
- Modules should expose `module.json` and an entrypoint implementing `Wonder\App\Module\Contracts\ModuleInterface`.
- The site enables modules in `custom/config/modules.php`.
- `Module\\Discovery` merges bundled, Composer, vendor filesystem, and local module sources.
- Composer discovery must remain compatible with both `vendor/composer/installed.php` and `installed.json`, plus filesystem fallback for `vendor/wonder-image/*/module.json`.
- `Module\\Registry` validates manifests, enforces dependencies, exposes module model/resource/lang/route paths, and merges permissions.
- `php forge publish:module <slug>` publishes a module's `paths.views` tree into the site at `custom/modules/<slug>/view/`. Runtime override support still depends on the module resolving that custom path before falling back to its package view path.

## Files to Avoid or Treat as Generated

- Never edit `vendor/`.
- Do not treat `docs/class/` as hand-maintained source; it is generated phpDocumentor output.
- Do not recreate `app/build/src/backend/*` or `app/build/table/*`.
