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

## Files to Avoid or Treat as Generated

- Never edit `vendor/`.
- Do not treat `docs/class/` as hand-maintained source; it is generated phpDocumentor output.
- Do not recreate `app/build/src/backend/*` or `app/build/table/*`.
