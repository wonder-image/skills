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

For frontend authentication, reuse `Wonder\Auth\Frontend\AuthProfile` and
opt-in `AuthRoutes`, not a site copy of the auth controller. Extend fields,
server validation, explicit write whitelists, federation policy and idempotent
business hooks together; configure email-verification permissions separately.
`AccountPanel` provides shared navigation/layout and personal-data hooks;
the customer record (contacts, addresses, billing) comes from the
`wonder-image/customer-account` module, enabled in `custom/config/modules.php`
before gestionale/ecommerce; payments stay module-owned. Signup fields beyond the core ones (phone, birth date, billing, shipping
address) are chosen in `custom/config/modules/customer-account.php` under
`signup.fields`, each `off` / `optional` / `required`: never copy the auth
controller or views to add them. Ecommerce exposes
`auth.profile`, `account.panel` and `account.navigation` in its configuration.
Use `auth.*` / `account.*` translation overrides, active-page form themes and
page-level alerts outside forms. Core guide: `docs/app/concetti/utenti/auth-frontend.md`.

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
5. Define backend inputs in `Resource::formSchema()` with `FormField::key(...)`.
6. Define route permissions in `permissionSchema()` (`PermissionSchema::for(static::class)->backendCrud([...])->apiCrud([...])`).
7. Define nav placement in `navigationSchema()`.
8. Run `composer dump-autoload`, then `php forge update --local`.
9. Use `CustomPageSchema` only when the feature is not a normal CRUD resource.

### Backend form checklist (site postilla)

Verified on `wonder-image/app` 2.4. The API contract stays in `wi-app/references/model-and-resource.md`; these are the traps a site hits.

- **Declare `->label()` on every `FormField`.** `labelSchema()` is applied only to fields that pass through `static::getInput()` in `formLayoutSchema()`. A Resource without a layout (or a field left out of it) falls back to `ucwords($column)`: the client sees "Logo Id", "Name", "Visible". Read the text from `static::labelSchema()` so table headers and form stay aligned.
- **Lay out short fields side by side** in `formLayoutSchema()` (`Card` + `columnSpan()`, `SectionTitle::make()` for a heading — `Card::title()` is the HTML `title` attribute, not a heading). Name, visibility and logo/image usually share one row.
- **Lists the client edits** (types, tags, categories) are a Resource of their own, linked with `selectSearch($options, true)` and `->quickCreate(TargetResource::class, ['name'], label: 'name', button: 'Aggiungi …')` so a value is created without leaving the form. The target must expose `apiSchema()->fields('store', [...])`; default missing required fields (e.g. `visible`) in its `mutateRequestValues()`.
- **A multiple select stored as JSON** comes back from the DB as a string: decode it in `mutateFormValues()` or the edit form shows nothing selected.
- **Do not use `sanitizeFirst()` on names and titles**: it capitalizes every word ("Villa A Cap-Martin"). Keep it for people's names only.
- **Years** are `->number()->decimal(0)->groupSeparator('')` in the form; normalize to digits in `mutateRequestValues()`.
- **Italian addresses**: province with the ready input `->states('IT')` (codes `BG` => `Bergamo`), municipality as a separate text field; print "Comune (BG)".
- **Show the image in the list**: `TableColumn::key('cover')->image()->size('little')->formatter(fn (array $row): string => MediaFile::find($id)?->thumbnailUrl() ?? '')` (the formatter returns the `src`; `size('little')` keeps the column as wide as the thumbnail).
- **Order the client controls**: `$orderColumn = 'position'` + `->reorderable()` in `tableLayoutSchema()`; read the frontend with the same order.
- **Schema changes reach the DB destructively**: `forge update` drops a column removed from `tableSchema()` and runs `MODIFY COLUMN` on a type change (it fails on rows that do not convert). Migrate the data in the same change or keep the old type.

## Backend-Editable Gallery Pattern

- Use a real resource-backed content model, not static arrays or loose files.
- Store the editable entity in project `Model` and `Resource` classes.
- Reuse existing media/resource patterns where possible for image selection and upload behavior.
- Keep frontend rendering separate from backend editing: backend through `Resource`, frontend through `custom/view/pages/frontend/*` and components.

## Images and icons: always from the backend

Every image of a public page — gallery photos, but also the hero, the service cards, the "about" photo — is a media of the backend library. No image files in `assets/{ASSETS_VERSION}/images/` and no `<img src>` written by hand: the client must be able to replace each one.

- **Fixed images of a page** (hero, service cards, about photo) are uploaded by the client in **Media → Immagini** with the **name equal to the title of the section** where they appear ("Impianti per la tua abitazione"). The page finds them by that name: no dedicated backend page, no extra table. The library derives the slug from the name with `create_link()`; look the media up the same way the framework does, `Media::find(['slug' => strtolower(create_link($title)), 'type' => 'image', 'deleted' => 'false'], 1)` then `MediaFile::fromRow()`, read the title from the same translation key the page prints, and list the expected names in `docs/ai/PRODUCT.md` (Asset paths) so the client knows what to upload.
- **A fixed image not uploaded yet tells its name.** Still render it through `__ri()`, pointing at `MediaFile::directoryUrl($type).rawurlencode($name)`: the file does not exist, and the lib's missing-image label (`data-wi-image-name`, `js/image.js`) writes the name on the box. Drop dots and slashes from the name (they would become an extension or a folder; the slug ignores them anyway) and style `.wi-image-missing::after` in the site CSS as a readable notice (`content: "Carica in Media: " attr(data-wi-image-missing)`, image at `opacity: 0`). `IMAGE_MISSING_LABEL=false` in `.env` turns the labels off.
- **Icons**: Bootstrap Icons stay `<i class="bi ...">`. Any other icon (brand pictograms from the design) is an svg uploaded in **Media → Icone** with the same naming rule (type `icon`).
- **Images of a record** (project gallery, client logo) are uploaded from the record's form: `->fileDragDrop('image')->mediaLibrary()->mediaSlug('{slug}')->mediaAlt('{name}')`, and `->maxFile(40)` for a gallery (the column holds the JSON list of ids, the first one is the cover, the alt text doubles as caption). Responsive sizes come from the resize queue (scheduler cron, or immediately when the scheduler is down); nothing to configure.
- **Render through `__ri()`**: read record ids in batch with `MediaFile::many()`, then `__ri($media->path())->attr('alt', $media->alt)` (`attr`, not `alt()`: the text is plain and the renderer escapes it once). Wrap both lookups in one site helper (e.g. `App\Support\SiteMedia::image(?MediaFile)` and `::named($title, $type)`) that returns null for a missing media, and print nothing — no placeholder text — when there is no image.
- **Logos** come from Media → Logo (`$SOCIETY->logo`); if the brand lettering is part of the logo file, do not repeat it as text next to it.

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
  - `php forge credentials`
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
