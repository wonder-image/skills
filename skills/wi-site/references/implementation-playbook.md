# Wonder Site Builder Playbook

Verified against `wonder-image/new-site` (https://github.com/wonder-image/new-site.git).

## Canonical Placement

- `custom/view/pages/{frontend,backend}/...` — site pages
- `custom/view/components/{frontend,backend}/...` — component overrides (mirror framework path)
- `custom/view/layout/frontend/...` — frontend layout overrides
- `custom/routes/` — site routes
- `custom/http/` — request handlers for custom routes (canonical pattern: `Route::get(path, $ROOT.'/custom/http/...')`, mirroring the framework's `$ROOT_APP.'/http/...'`). Simple page routes can also dispatch directly to a view file (`$ROOT.'/custom/view/pages/<area>/<page>.php'`) without a handler — that is what the scaffold does for `/contact/`, `/`, and the legal pages.
- `custom/config/{config,lang,navigation,permissions}.php` — site configuration
- `app/Models/`, `app/Resources/` — `App\\` models, resources, support classes (PSR-4)
- `assets/{ASSETS_VERSION}/{css,js,icons,images}/` — site assets (discover the active version dynamically; do not hardcode `0.0/`)
- `lang/{locale}/*.json` — translations (any number of JSON files; new files are picked up automatically)

## New Page Workflow

1. Inspect existing page and section components that already express the target style.
2. Reuse or override components before creating new ad hoc markup.
3. Create the page under `custom/view/pages/{frontend,backend}/...`.
4. Add or update routing only where the project expects it (frontend routes in `custom/routes/`, resource routes are generated from the Resource).
5. Move all visible copy into a JSON file under `lang/{locale}/` — typically `pages.json`, but any custom filename (e.g. `lang/{locale}/example.json`) is picked up automatically.
6. Use `__t(...)` for all rendered text. **Prefer `__r(string $name, array $parameters = [])`** for URLs (route name → URL, e.g. `__r('resource.projects.list')`). Use `__u(string $path)` only when no named route exists and a free-path URL is unavoidable (`__u('contact')`).

## New Layout (layout base) Workflow

Use this when a page needs a different chrome from the project default (e.g. a public auth page with a minimal header and minimal footer, a checkout page with no nav, an embedded view with no body padding).

Framework reference: `vendor/wonder-image/app/app/view/layout/{frontend,backend}/*.php` ships `base.php` (document shell) and specialized wrappers (`auth.php`, `main.php`, `form.php`, `list.php`, `show.php`). Read them before writing a new layout.

Steps:

1. Decide whether the layout is **frontend** or **backend** and put the file under `custom/view/layout/<area>/<name>.php`.
2. Inside the file, nest into a base via `\Wonder\View\View::layout('<area>.base')` (or another existing layout when you want to inherit its chrome).
3. Render the surrounding chrome with `\Wonder\View\View::component('<area>.layout.<piece>')` — use existing components (`header`, `footer`, `header-minimal`, `footer-minimal`, …) before inventing new ones.
4. Output the page content placeholder with `<?= $PAGE_CONTENT ?>`.
5. Close with `\Wonder\View\View::end();`.
6. Use the new layout from a page with `\Wonder\View\View::layout('<area>.<name>'); ... \Wonder\View\View::end();`.

Reference example — minimal auth wrapper, mirrors the framework's `backend/auth.php` pattern:

```php
<?php \Wonder\View\View::layout('frontend.base'); ?>

    <?= \Wonder\View\View::component('frontend.layout.header-minimal') ?>

    <section>
      <div class="content content-medium">
          <?= $PAGE_CONTENT ?>
      </div>
    </section>

    <?= \Wonder\View\View::component('frontend.layout.footer-minimal') ?>

<?php \Wonder\View\View::end(); ?>
```

If the `header-minimal` / `footer-minimal` components do not exist yet, create them under `custom/view/components/frontend/layout/` mirroring the framework component path — do not inline raw markup in the layout file.

## New Resource Workflow

For the full Model / Resource API contract see `wi-app/references/model-and-resource.md`. Site-side steps:

1. Create the project `Model` under `app/Models/<Name>.php` (namespace `App\Models`).
2. Define SQL in `Model::tableSchema()`. Prefer `static::sqlColumnsFromDataSchema([...])` to derive columns from the data schema.
3. Define data preparation in `Model::dataSchema()` using `UploadSchema as Field` declarations (`Field::key('name')->text()->required()`, etc.).
4. Create the project `Resource` under `app/Resources/<Name>Resource.php` (namespace `App\Resources`), bound via `public static string $model = \App\Models\<Name>::class;`.
5. Define backend inputs in `Resource::formSchema()` with `FormInput::key(...)`.
6. Define route permissions in `permissionSchema()` (`PermissionSchema::for(static::class)->backendCrud([...])->apiCrud([...])`).
7. Define nav placement in `navigationSchema()`.
8. Run `composer dump-autoload`, then `php forge update --local`.
9. Use `CustomPageSchema` only when the feature is not a normal CRUD resource.

## Backend-Editable Gallery Pattern

- Use a real resource-backed content model, not static arrays or loose files.
- Store the editable entity in project `Model` and `Resource` classes.
- Reuse existing media/resource patterns where possible for image selection and upload behavior.
- Keep frontend rendering separate from backend editing: backend through `Resource`, frontend through `custom/view/pages/frontend/*` and components.

## Component Override Rule

1. Find the source component path in the framework (`vendor/wonder-image/app/...`).
2. Mirror that path under `custom/view/components/{frontend|backend}/...`.
3. Override only the needed component, not the whole tree.
4. Preserve expected variables and render contract.

## Forge Rules

- Allowed:
  - `php forge update --local`
  - `php forge start`
- Forbidden from this skill:
  - `php forge config`
  - `php forge provision`
  - `php forge db:init`
  - `php forge build`

## Explicitly Forbidden Patterns

- No direct edits to `vendor/`, `node_modules/`, or `assets/lib/wonder-image/dist/`
- No hardcoded user-facing text in PHP — must go through `__t(...)` + a JSON file under `lang/{locale}/`
- No hardcoded language prefixes in URLs — **prefer `__r(string $name, array $parameters = [])`**; fall back to `__u(string $path)` only when there is no named route
- No hardcoded `{ASSETS_VERSION}` — discover the active version directory dynamically
- No custom CSS inside `assets/{ASSETS_VERSION}/css/set-up/` — that folder is reserved for design-system token files (`color.css`, `root.css`)
- No blind CSS / JS generation without consulting `wonder-image/lib` first (see `references/style-and-lib.md`)
