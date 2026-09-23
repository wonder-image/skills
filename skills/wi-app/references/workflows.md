# Wonder Image App Workflows

## New project startup

Use the complete domain with dots replaced by hyphens as the project folder: `wonderimage.it` becomes `wonderimage-it`. Define `NOME_PROGETTO="wonderimage-it"` once, then run `composer create-project wonder-image/new-site:dev-main "$NOME_PROGETTO"`, `cd "$NOME_PROGETTO"`, `composer update`, `git init`, `git remote add origin "https://github.com/wonder-image/${NOME_PROGETTO}.git"`, `php forge provision`, `php forge db:init`, `php forge update --local`, `php forge start`, in that order. The scaffold includes composer.lock: create-project installs locked dependencies; the explicit composer update refreshes them. Both invoke `forge config`. After startup run `git add .`, `git commit -m "Initial commit"`, `git push -u origin HEAD` from the project directory (another terminal if the PHP server is running), then GitHub Desktop > Add > Add existing repository. Set origin before provision so it selects the wonder-image organization instead of the authenticated personal account. If origin exists, inspect it and use git remote set-url only when incorrect. Provision creates the remote repository when missing; Desktop Publish repository or gh repo create are alternatives only when it does not yet exist.

`APP_DOMAIN=wonderimage.it` is distinct from Herd's `APP_URL=https://wonderimage.test`. Config preserves an existing APP_URL during Composer updates; `forge start` repairs a stale local URL. Config runs `npm install wonder-image` and `npm install`, which can update the JS dependency and lockfile. It does not explicitly upgrade the npm executable, but may install Node (including npm) via Homebrew if missing. This documents the user setup workflow; it does not authorize running provisioning during routine validation.

## Versioning and releases

The framework version lives only in `composer.json` `"version"`; `Wonder\App\Version::get()` resolves `APP_VERSION` at bootstrap (package manifest, then `InstalledVersions`, then `dev`) and `Version::label()` appends `dev-main@<hash>` for branch installs. Never hardcode a version in `wonder-image.php`. Release from the package root on a clean, pushed `main` with `composer release -- X.Y.Z` (script `bin/release.php`, gitignored and local-only; pre-release: `X.Y.Z-alpha|beta|rc.N`; also `patch|minor|major`, `--dry-run`, `--no-github`). It bumps composer.json, commits `Release X.Y.Z`, tags `vX.Y.Z`, pushes, and runs `gh release create`. Tags must be `vX.Y.Z` (not `v.X.Y.Z`) and match composer.json at that commit, or Packagist skips them. Releasing publishes to GitHub/Packagist: only run it when the user asks.

## Decide Where the Change Belongs

- Use the **framework** when changing framework behavior, bootstrap, registries, route generation, console command source, shared resources, or architecture conventions.
- Use a **site** when changing project-specific models, resources, config, templates, content, or enabled-module state.
- Use an external **module** package when the feature is reusable and should not be embedded into the framework.

## Preferred Placement for New Work

- Prefer `class/App/*` PSR-4 classes for new logic.
- Use `app/*` when you must interact with legacy runtime handlers, bootstraps, routes, or helpers that the new layer still delegates to.
- For package APIs, put handlers in `app/http/api/*` and expose them from `app/config/routes/route.api.php`. Do not add files back under `app/api/*`.
- Keep default providers split by responsibility:
  - `Wonder\\App\\RuntimeDefaults` for runtime fallback values used during rendering or config bootstrap.
  - `Wonder\\App\\SeedDefaults` for idempotent seed/bootstrap payloads used by `build/row`, setup commands, and empty singleton forms.
- For backend forms:
  - put SQL structure in `Model::tableSchema()`
  - put data transformation and persistence rules in `Model::dataSchema()`
  - put backend inputs in `Resource::formSchema()`
- Use `CustomPageSchema` for non-CRUD backend pages.
- Use `FormField::repeater()`, `RepeaterColumn`, and `Wonder\App\Support\Repeater` for repeatable rows.
- `TableSync` orders imports from foreign keys declared in `Model::tableSchema()`: referenced synchronized tables are populated before dependent tables, while independent tables retain their configured order.
- **All form inputs go through the `FormField` hierarchy**, on the frontend Wonder theme and the backend Bootstrap theme alike. Declare with `FormField::key(...)` / `RepeaterColumn::key(...)` (or directly with a typed `Inputs\Input*`); render through `Input::render($theme)`. Never emit raw `<input>` / `<select>` / `<textarea>` HTML and never wrap input rendering in custom functions that bypass the schema. Missing input types are added at the framework layer (typed input with `element()` + `FormField` helper + Wonder/Bootstrap renderer under `class/Themes/*`), not patched into the call site. Full rule in [`model-and-resource.md`](model-and-resource.md#formfield-hard-rule).

## High-Risk Areas

- `wonder-image.php`: package bootstrap and root resolution
- `class/App/Credentials.php`: `.env` and credential loading
- `class/App/ModelRegistry.php` and `class/App/ResourceRegistry.php`: discovery precedence
- `class/App/Module/*`: module discovery, validation, state, and registry behavior
- `app/config/routes/*`: frontend, backend, and API route integration
- `app/http/*`: request handlers used by generated routes
- `class/Console/*`: commands that are sourced here but executed from sites

## Validation Matrix

- Always run `php -l` on touched PHP files.
- Run `composer dumpautoload` when classes are added, moved, or renamed.
- If you change any of these, validate from a site:
  - `class/Console/*`
  - `class/App/Resource*`
  - `class/App/Model*`
  - `app/config/routes/*`
  - `app/http/*`
  - `wonder-image.php`
- Site validation commands:
  - `php forge update --local`
  - `php forge start`
- For Herd-specific local routing changes, also run `herd restart`.
- For missing uploads under Herd, configure `MEDIA_FALLBACK_URL` and keep `APP_URL` aligned with the local HTTPS origin. The driver must send the media redirect directly, not return a generated PHP file as a static asset.

## Repo-Specific Gotchas

- This repo has no standalone `forge` executable at package root.
- `php forge ...` examples in docs are integration commands for the site, not for this package root.
- Some fixes require testing against a sibling site because the real runtime lives there.
- `app/config/app/table.php` still loads PHP files from `app/build/table/`; do not add new files there.
- `SortableInput` is deprecated. Keep it only for compatibility.
- The canonical module pattern is Composer-package based, not copy-paste under `custom/...`.
- For module view overrides, use `php forge publish:module <slug>` from the site root. It copies the module `paths.views` tree into `custom/modules/<slug>/view/`; the module entrypoint should resolve that custom path before falling back to package views.

## Documentation Expectations

- Frontend dependencies defer scripts by default and inline bounded `wi-lib`, Swiper, and structural `wi-frontend` CSS automatically. Relative asset URLs in `wi-frontend` are resolved before inlining; unsuitable or oversized CSS falls back to an external link. Google Fonts from `css_font` load asynchronously with `display=swap`. Test widget activation after lazy reCAPTCHA loading.

- For frontend asset loading, keep inline dependency consumers on DOMContentLoaded/loaded. Ordered deferred loading is the default and Moment must be requested explicitly; `Dependencies::deferFrontend(false)` is only a temporary opt-out for legacy sites. `php forge update` refreshes the managed `WONDER PERFORMANCE` block in `.htaccess`; put custom rules outside its markers. Responsive image URLs are versioned when local files exist. See the framework's `docs/app/concetti/frontend-performance.md` for the contract.

- When changing architecture, rendering flow, bootstrap/runtime setup, layout structure, or developer-facing conventions, update the related GitBook docs under `docs/app/*` in the same work.
- If the change is narrow and internal, keep docs unchanged unless behavior or conventions actually moved.
