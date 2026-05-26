# Model and Resource

## Purpose

- Canonical reference for **using and extending** the `Model` and `Resource` classes provided by `wonder-image/app`.
- Use this when defining a new Model or Resource (in the framework's own `class/App/Models` and `class/App/Resources`, in a site's `app/Models` and `app/Resources`, or inside an external module package).

## When this reference applies

- Defining a new Model or Resource.
- Configuring `tableSchema()`, `dataSchema()`, `formSchema()`, `permissionSchema()`, `navigationSchema()`, `querySchema()`, or repeater fields.
- Wiring a Resource into the generated backend / API CRUD route system.
- Adding a `CustomPageSchema` for a non-CRUD backend page.

## How to USE, not how to MODIFY

This document covers **extension, configuration, and registration** of Model / Resource subclasses — i.e. the public contract of `wonder-image/app`.

It does **not** cover modifying the base classes themselves (`class/App/Model.php`, `class/App/Resource.php`). Treat the base classes as a library contract: read them when needed, but change them only on an explicit user request. If a task seems to require base-class changes, surface that as a framework-level decision before editing.

## Placement (where new classes go)

- **Framework default models / resources** → `class/App/Models`, `class/App/Resources` inside `wonder-image/app` (namespace `Wonder\App\Models\...`, `Wonder\App\Resources\...`).
- **Site project** → `app/Models`, `app/Resources` inside a project scaffolded from `wonder-image/new-site` (namespace `App\Models\...`, `App\Resources\...`).
- **External module package** → per the module's own PSR-4 root, declared in its `composer.json` and exposed through `module.json` + an entrypoint implementing `Wonder\App\Module\Contracts\ModuleInterface`.

The ModelRegistry / ResourceRegistry discovery and precedence rules are documented in [`architecture.md`](architecture.md). Subclasses placed in higher-precedence locations override lower-precedence ones.

## Model — how to define one

### Class skeleton

```php
<?php

namespace App\Models;

use Wonder\App\Model;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

final class Project extends Model
{
    public static string $table  = 'projects';
    public static string $folder = 'projects';      // upload folder for any file/image field
    public static string $icon   = 'bi bi-folder';  // used by backend navigation defaults

    public static function tableSchema(): array
    {
        return [
            Column::key('position')->int(),
            ...static::sqlColumnsFromDataSchema(['slug', 'name', 'visible']),
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('slug')->text()->slug(),
            Field::key('name')->text()->sanitizeFirst()->required(),
            Field::key('visible')->text(),
        ];
    }
}
```

Required overrides (declared `abstract` on the base): `tableSchema()` and `dataSchema()`. Everything else has a default.

Optional public properties on the base, override only when needed:

- `$table` — SQL table name (required in practice).
- `$folder` — upload subfolder for file / image fields tied to this model.
- `$icon` — default Bootstrap-icon class used by `Resource::icon()` if the resource does not declare its own.
- `$defaultCondition` — default WHERE filter, defaults to `['deleted' => 'false']` so soft-deleted rows are excluded. Set to `null` or `[]` to disable.
- `$dbHostname`, `$dbUsername`, `$dbPassword`, `$dbName` — per-model connection override. Defaults pick up runtime credentials via `Credentials`.

### `tableSchema()`

`abstract public static function tableSchema(): array`

Returns a flat array of `Wonder\Sql\TableSchema` column declarations describing the SQL structure. Two common patterns:

- **Derive columns from `dataSchema()`** with `static::sqlColumnsFromDataSchema([...])`. The base reads each `Field`'s own `sqlSchema()` and produces an `SqlColumn` automatically (type, length, null, default, enum, index, primary, unique, foreign key, on update, on delete, after).
- **Declare columns explicitly** with `Column::key('name')->int()`, `->text()`, `->bool()`, etc., for columns that are not in `dataSchema()` (e.g. `position`, audit columns added manually).

Spread the two together when needed:

```php
return [
    Column::key('position')->int(),
    ...static::sqlColumnsFromDataSchema(['slug', 'name', 'visible']),
];
```

`tableOptions(): array` (optional override) — table-level options such as `audit_columns`, `audit_auto_columns`. Default `[]`.

`tablePseudos(): array` (optional override) — additional column-like schemas merged in by `rawTableSchema()`. Default `[]`.

### `dataSchema()`

`abstract public static function dataSchema(): array`

Returns an array of `Wonder\Data\Fields\Field` objects (in practice, use `Wonder\Data\UploadSchema as Field` for the convenient builder). Each field is keyed via `Field::key('name')` and chained with the type and constraints:

```php
return [
    Field::key('slug')->text()->slug(),
    Field::key('name')->text()->sanitizeFirst()->required(),
    Field::key('email')->text()->email()->required(),
    Field::key('picture')->upload()->image(),   // file/image field; storage path comes from $folder
    Field::key('visible')->text(),
];
```

The base uses the data schema for three purposes:

1. **SQL generation** via `sqlColumnsFromDataSchema()` (`tableSchema()` typically calls this).
2. **Validation** of incoming values via `validate()` (each field's own `validate()` runs).
3. **Persistence formatting** via `prepare()` (each field's own `format()` runs, including sanitize, slug, file storage, JSON encoding).

### Query helpers

The base exposes a static query API. All read methods return rows that already filter on `$defaultCondition` (soft-deleted rows excluded):

| Method | Purpose |
| --- | --- |
| `query(): Wonder\Sql\Query` | Low-level builder for the configured connection. |
| `all(string\|array $columns = '*'): array` | Every non-deleted row. |
| `find(condition?, limit?, order?, direction?, columns?): mixed` | Filtered fetch. |
| `findById(int\|string $id): mixed` | Single row by id. |
| `get*` | Aliases (`getAll`, `get`, `getById`) of the corresponding `*` methods. |
| `safeAll()`, `safeFind()`, `safeFindById()` | Same as above, but normalize the result for API output (booleans, JSON decode, file URLs in an `api` payload). |
| `create(array $values): object` | Validates with `validate()`, prepares with `prepare()`, then inserts. Returns the SQL response or a validation-failure object. |
| `update(array $values, int\|string $id): object` | Same pattern as `create()`, for updates. |
| `createUpdate(array $values, int\|string\|null $id = null): object` | Create if `$id` is empty, otherwise update. |
| `delete(int\|string $id): object` | Hard delete by id. Soft delete is achieved by updating `deleted = 'true'` through normal `update()`. |

Use the `safe*` variants whenever the result is going out to a client (API or anything serialized to JSON).

### Field-to-SQL conversion

`Model::sqlColumnFromField(?DataField $field): ?SqlColumn` builds an `Wonder\Sql\TableSchema` column from a single data field by reading the field's own `sqlSchema()` and `getSchema()`. Supported keys: `type`, `length`, `null`, `default`, `enum`, `index`, `primary`, `unique`, `foreign_table` + `foreign_key`, `foreign_on_update`, `foreign_on_delete`, `after`, `on_update`, `auto_increment`.

`Model::sqlColumnsFromDataSchema(array|string|null $only = null)` runs the conversion across all data fields, optionally restricted to a subset. This is the recommended way to keep SQL and data definitions in sync. Manual `Column::key(...)` declarations are only needed for columns that are not in `dataSchema()`.

### Lifecycle / persistence hooks

- `validate(array $values, string $prefix = ''): object` — runs `Field::validate()` for every field in `dataSchema()`. Returns `{ valid: bool, response: array }`. `create()` and `update()` call it implicitly; call it directly when you need to validate before doing anything else.
- `prepare(array $values, string $prefix = ''): object` — runs `Field::format()` for every field. Produces the final `[column => value]` array used for the actual INSERT / UPDATE.
- Soft delete is opt-in via columns: if `$defaultCondition` is set and a `deleted` column exists (or audit columns are enabled), reads automatically exclude rows where `deleted = 'true'`. There is no `softDelete()` method on the base — set `deleted = 'true'` through `update()`.
- File handling (image / file fields) is delegated to `Wonder\App\Support\MediaFileManager`. The `safe*` reads expand stored filenames into URLs using `$folder` and the field's `dir` schema. No need to wire this manually — just declare `Field::key('picture')->upload()->image()` and use `static::$folder` to set the upload subdirectory.
- There is **no `beforeSave` / `afterSave` hook on the Model itself**. Pre/post processing lives on the Resource (see `mutateRequestValues`, `afterStore`, `afterUpdate`, `afterDelete` below) so that the same Model can be used by multiple Resources with different behavior.

### Minimal Model example

```php
<?php

namespace App\Models;

use Wonder\App\Model;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

final class Project extends Model
{
    public static string $table  = 'projects';
    public static string $folder = 'projects';
    public static string $icon   = 'bi bi-folder';

    public static function tableSchema(): array
    {
        return [
            Column::key('position')->int(),
            ...static::sqlColumnsFromDataSchema([
                'slug',
                'name',
                'description',
                'cover',
                'visible',
            ]),
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('slug')->text()->slug(),
            Field::key('name')->text()->sanitizeFirst()->required(),
            Field::key('description')->text(),
            Field::key('cover')->upload()->image(),
            Field::key('visible')->text(),
        ];
    }
}
```

After adding or moving the file, run `composer dump-autoload` from the project that owns it (framework, site, or module).

## Resource — how to define one

### Class skeleton

```php
<?php

namespace App\Resources;

use Wonder\App\Resource;
use Wonder\App\ResourceSchema\ApiSchema;
use Wonder\App\ResourceSchema\FormInput;
use Wonder\App\ResourceSchema\NavigationSchema;
use Wonder\App\ResourceSchema\PermissionSchema;
use Wonder\App\ResourceSchema\TableColumn;

final class ProjectResource extends Resource
{
    public static string $model = \App\Models\Project::class;

    public static string $orderColumn    = 'position';
    public static string $orderDirection = 'ASC';

    public static function path(): string { return 'projects'; }
    public static function icon(): string { return 'bi bi-folder'; }

    // ...schemas (see below)
}
```

Required: `public static string $model` pointing to the FQN of the bound Model. The base validates that the class exists and extends `Wonder\App\Model`.

Optional public properties on the base (override only when needed):

- `$path` — backend / API slug prefix. If unset, falls back to `Model::$folder`.
- `$condition` — default WHERE filter for listing. Defaults to `['deleted' => 'false']`.
- `$limit`, `$orderColumn`, `$orderDirection` — listing defaults exposed via `querySchema()` for any route consuming the resource list.

Override the static helpers `path()`, `icon()`, `slug()` only when the value is computed; otherwise set the property.

`labelSchema(): array` and `textSchema(): array` are the canonical places to keep all human-readable labels and short noun forms used by the backend (`label`, `plural_label`, etc.). Form fields without an explicit `label()` automatically pick up the matching key from `labelSchema()`.

### `FormInput` / `FormField` hard rule

Every form input — in **both** the frontend Wonder theme and the backend Bootstrap theme — must be declared through the `FormField` class hierarchy. There is exactly one sanctioned path:

- **Declaration**: `FormInput::key($name)->...` (or `RepeaterColumn::key($name)->...` inside a repeater row, or the `FormSchema::for(...)` builder when you need grouping / sidebar fields).
- **Render**: `FormField::render($theme)`, which dispatches through `class/App/Support/FormFieldElementFactory.php` to a `Wonder\Elements\Form\Components\*` element, which the theme resolver in `class/Themes/Resolver.php` renders with either `class/Themes/Wonder/...` (frontend, `wi-*` markup) or `class/Themes/Bootstrap/...` (backend, `form-floating` / Bootstrap markup).

**Do not:**

- write raw HTML inputs (`<input>`, `<select>`, `<textarea>`, `<input type="file">`, etc.) in pages, components, layouts, or partials — neither under `class/App/*` nor in a site's `custom/view/*`. This bypasses theme dispatch, label/error wiring, attribute parsing, value normalization, and the file / repeater / date helpers.
- introduce free functions or page helpers (`render_input(...)`, `text_field(...)`, etc.) that emit HTML directly. The only function-style helper allowed is the chainable builder API on `FormField` itself.
- inline a bespoke component in a page just to "skip the schema". If the page is non-CRUD, model it as a `CustomPageSchema` so its inputs still go through `FormInput`.
- hand-pick markup for one theme only. The same `FormInput` declaration must work for the Wonder theme on the frontend **and** the Bootstrap theme on the backend.

**If an input type is missing**, the fix is at the framework layer, not at the call site:

1. add the chainable helper on `FormField` (and/or expose it on `FormSchema`),
2. map the helper key in `FormFieldElementFactory::make()` to an existing or new `Wonder\Elements\Form\Components\*` element,
3. add the matching renderer under `class/Themes/Wonder/` and `class/Themes/Bootstrap/` so both themes are covered,
4. then declare the field with `FormInput::key(...)->newHelper(...)` as usual.

Reference implementations: every `*Resource::formSchema()` under `class/App/Resources/`, the contact-style example in [`CustomPageSchema`](#custompageschema-non-crud-backend-pages) below, and the renderer table in `FormFieldElementFactory` (lines mapping `text`, `select`, `inputFileDragDrop`, `inputRepeater`, etc.).

### `formSchema()`

`public static function formSchema(): array`

Returns the list of inputs rendered by the backend form. Each entry is a `FormInput` (which extends `FormField`):

```php
public static function formSchema(): array
{
    return [
        FormInput::key('name')->text()->required(),
        FormInput::key('description')->textarea(),
        FormInput::key('cover')->inputFileDragDrop('image', 'classic'),
        FormInput::key('visible')->select([
            'true'  => 'Visibile',
            'false' => 'Nascosto',
        ])->value('true')->required(),
    ];
}
```

Type helpers chainable on `FormInput::key($name)` (defined on `FormField`, full list in `class/App/ResourceSchema/FormField.php`):

- text inputs: `text`, `textGenerator(callback?, buttonLabel?)`, `hidden`, `email`, `tel`, `phone`, `url`, `number`, `price`, `percentige`, `password`, `color`
- date/time: `textDate`, `textDatetime`, `dateInput(min?, max?)`, `dateRange(min?, max?)`, `timeInput(step = 900)`
- text areas: `textarea(version?)` (pass a version string to opt into the rich-text editor)
- choice: `select(options, version?)`, `radio(options, searchBar = false)`, `selectSearch(options, multiple = false, version?)`, `checkbox`, `checkTree(options, searchBar, inputType)`, `dynamicCheck(url, inputType)`, `checkBoolean(values, trueLabel?, falseLabel?)`
- geo: `country(stateField?)`, `states`, `phonePrefix`, `googleAddress(restriction, alias?)`
- files: `inputFile(file = 'image')`, `inputFileDragDrop(file = 'image', uploader = 'classic')`
- repeatable: `repeater([RepeaterColumn, ...])`

Each helper sets the internal `helper` key, which `class/App/Support/FormFieldElementFactory.php` maps to a concrete `Wonder\Elements\Form\Components\*` element. Adding a new type means adding the helper here **and** the mapping there (plus renderers under `class/Themes/Wonder/` and `class/Themes/Bootstrap/`) — see the hard rule above.

Common chainable modifiers from `FormField`: `.label(string)`, `.value(mixed)`, `.required()`, `.disabled()`, `.readonly()`, `.multiple()`, `.attribute(string)` (parsed by `AttributeString`), `.options(array)`, `.searchBar(bool)`, `.columnSpan(int)`, `.error(string)`, `.prepare(string|array, mixed)`, `.context(string|array, mixed)`, `.nested(bool)`, `.old()`, `.file(type)`, `.uploader(name)`, `.dateMin(?string)`, `.dateMax(?string)`, `.timeStep(?int)`, `.maxSize(int)`, `.maxFile(int)`, `.extensions(array)`, `.storeAs(string)`, `.inputName(string)`, `.version(?string)`, `.relation(object)` (used by `repeater` for `RepeaterRelation`).

For layouts, override `formLayoutSchema(): ?Form` and compose Cards / Containers around `static::getInput('field_name')`. See `class/App/Resources/Css/CssAlertResource.php` for a full layout example.

Sidebar fields use the same builder via `FormSchema::for(...)->sidebarField($field)` or `->sidebarFields([...])`.

### `permissionSchema()`

```php
public static function permissionSchema(): PermissionSchema
{
    return PermissionSchema::for(static::class)
        ->backendCrud(['admin', 'administrator'])
        ->apiCrud(['admin', 'administrator']);
}
```

Construct with `PermissionSchema::for(static::class)`. Available builder methods:

- `.backend(string|array $actions, array $authorities = [])` — allow specific backend actions for the given authorities. Valid action keys: `list`, `create`, `store`, `view`, `edit`, `update`, `delete`.
- `.api(string|array $actions, array $authorities = [])` — same for API. Valid action keys: `index`, `store`, `show`, `update`, `destroy`.
- `.backendCrud(array $authorities = [])` — shortcut for all seven backend actions.
- `.apiCrud(array $authorities = [])` — shortcut for all five API actions.
- `.allow(string $area, string|array $actions, array $authorities)` — generic form when you have computed values.

The schema is consumed by `ResourceRouteRegistrar`: each emitted route is restricted to the listed authorities via `Route::permit(...)`. Permission **keys** themselves are defined in the builder registry rooted at `app/config/app/permission.php` — see [`permissions-and-users.md`](permissions-and-users.md) for that side.

`$authorities = []` means "no extra gate on top of being authenticated for the area".

### `navigationSchema()`

```php
public static function navigationSchema(): NavigationSchema
{
    return NavigationSchema::for(static::class)
        ->section('Avvisi', 'notices', 'bi-megaphone')
        ->title('Annunci')
        ->order(20)
        ->authority(['admin', 'administrator']);
}
```

Builder methods on `NavigationSchema::for(static::class)`:

- `.enabled(bool = true)` — toggle the nav entry on/off.
- `.section(string $title, string $folder, string $icon, array $authority = [])` — group this resource under a named section (the section's title, folder slug, icon, and optional authority gate).
- `.title(string)` — entry title, defaults to `Resource::titleLabel()`.
- `.order(int)` — sort weight, default 100.
- `.file(string)` — which page to link to, default `list`.
- `.authority(array)` — authority gate on this specific entry.

### `tableSchema()` and `tableLayoutSchema()` (listing)

The Resource listing has two complementary schemas:

- `public static function tableSchema(): array` — columns rendered in the listing table. Each entry is a `TableColumn` (extends `Wonder\Sql\TableSchema\Column`). Common builder methods: `TableColumn::key($name)->text()`, `->badge()`, `->image()`, `->button()`, `->link('edit'|'view')`, `->function($fn, ...$args)`, `->actions(['edit','delete'])`, `->size('little'|'medium'|'large')`, `->align('start'|'center'|'end')`, `->columnSpan(int)`.
- `public static function tableLayoutSchema(): TableLayoutSchema` — chrome around the table. Builder methods on `TableLayoutSchema::for(static::class)`:
  - `.title(bool|string $enabled = true, ?string $text = null)` — page title block (`title('Lista X')` is the common form).
  - `.results(bool = true)` — toggle the result-count line.
  - `.buttonAdd(bool|string $enabled = true, ?string $label = null)` — top-right "Add" CTA.
  - `.filters(bool $search = true, bool $limit = true)` — search box and per-page limit selector.
  - `.searchFields(array)` — columns the search box queries.
  - `.customFilters(array)` — extra filters (typically built from `FormInput` so they go through the same `FormField` render path).

Defaults (set in the constructor): title enabled, results enabled, button-add enabled, search + limit enabled. Override only what differs from those defaults.

### `apiSchema()`

`public static function apiSchema(): ApiSchema`

Drives `ResourceRouteRegistrar`'s API emission and the field projection used by the generated handlers. Builder methods on `ApiSchema::for(static::class)`:

- `.enabled(bool = true)` — toggle the entire API surface for this resource.
- `.route(string $action, bool $enabled = true)` — toggle a single action. Valid keys: `index`, `store`, `show`, `update`, `destroy`.
- `.only(array $routes)` — whitelist exactly which actions to expose (everything else is disabled).
- `.fields(string $action, array $fields)` — projection for that action's response / accepted payload. Action keys: `index`, `show`, `store`, `update`.
- `.guard(string $guard)` — auth guard for the generated routes (default `api_internal_user`).
- `.pagination(bool $enabled = true, int $defaultLimit = 25, int $maxLimit = 100)` — pagination envelope on `index`.

The route gating is still driven by `permissionSchema()->apiCrud(...)` / `->api(...)` — `apiSchema()` only shapes the surface, not who can call it.

### `querySchema()`

Default implementation returns the resource-level listing defaults:

```php
return [
    'condition' => static::$condition,
    'limit'     => static::$limit,
    'order'     => [
        'column'    => static::$orderColumn,
        'direction' => static::$orderDirection,
    ],
];
```

Override when the listing needs computed filters, dynamic limits, or sort logic that cannot live on the static properties. The returned shape must keep the `condition` / `limit` / `order` keys — backend listing code reads them directly.

### Repeater fields

A repeater field on the parent resource is declared on its `FormInput` and configured with one or more `RepeaterColumn` definitions:

```php
FormInput::key('allowed_domains')
    ->repeater([
        RepeaterColumn::key('allowed_domains')
            ->text()
            ->label('Dominio')
            ->columnSpan(11),
    ])
    ->repeaterSortable()
    ->repeaterAddLabel('Aggiungi dominio')
    ->label('Domini');
```

`RepeaterColumn` extends `FormField`, so the full input builder API is available inside a row (text, select, file, etc.).

Repeater rows can either be **inline JSON on the parent record** (no relation needed) or **rows stored in a related table**. For the related-table case, attach a `RepeaterRelation` **on the parent `FormInput`** (the one that owns the `->repeater([...])` call), via the public `->relation(...)` method on `FormField`. The relation lands in the field's context under `relation`, where `Resource::repeaterRelations()` reads it:

```php
FormInput::key('steps')
    ->repeater([
        RepeaterColumn::key('title')->text()->required(),
        RepeaterColumn::key('position')->number()->columnSpan(2),
    ])
    ->repeaterSortable()
    ->relation(
        RepeaterRelation::make($table = 'project_steps', $parentKey = 'project_id')
            ->positionKey('position')
            ->softDelete(true, 'deleted')
            ->resource(\App\Resources\ProjectStepResource::class)
    );
```

`->relation(...)` lives on `FormField` (so it works on `FormInput` and `RepeaterColumn` alike), but the resource layer reads it **only from the top-level repeater field** in `formSchema()` — attaching it to a child `RepeaterColumn` is a no-op for the sync helpers.

`RepeaterRelation` accepts either a bare table + key pair or a full `->resource($resourceClass)` binding that derives the table, prepare schema, and folder from the related resource. The parent resource's static helpers `syncRepeaterRelations($parentId, $post, $files, $action, $context)`, `appendRepeaterRelationsToItem($item)`, `appendRepeaterRelationsToCollection($items)`, and `hydrateRepeaterFormValues($values, $parentId, $post, $files)` handle the read / write cycle. Override `prepareRepeaterRelationRow($inputName, $payload, $row, $existingRow, $action, $context)` to mutate row payloads before they hit the related table.

The low-level helpers in `Wonder\App\Support\Repeater` (`hasRowsInRequest`, `rowsFromRequest`, `loadRelatedRows`, `syncRelatedRows`) are available when you need to drive the sync manually.

### Custom backend routes

Two extension points let a Resource go beyond the seven generated CRUD actions:

- `customBackendPages(): array` — return a list of action names (`'create'`, `'store'`, `'edit'`, `'update'`, `'view'`, `'delete'`, `'list'`) that the resource handles itself. The route registrar will **skip** generating those default endpoints so you can register your own without conflicts.
- `registerBackendRoutes(string $rootApp, string $slug): void` and `registerApiRoutes(string $rootApp, string $slug): void` — called by the route registrar after the generated routes are emitted. Register custom routes with `Wonder\Http\Route::get(...)` / `post(...)` / etc., and gate them with `->permit($authorities)` against the same permission keys used in `permissionSchema()`.

Use this when a resource needs an extra route (e.g. a dashboard page, an export endpoint, a JSON dependency for select inputs) without becoming a full `CustomPageSchema`.

### Lifecycle hooks

Override on the Resource subclass to inject behavior around the CRUD pipeline:

- `mutateRequestValues(array $values, string $action, string $context = 'backend', ?array $oldValues = null): array` — change incoming request values before validation / persistence. Typical use: auto-generate a slug from a name (see `AnnouncementResource`).
- `mutateFormValues(array $values, string $mode, string $context = 'backend'): array` — change values right before the form is rendered (e.g. pre-fill defaults).
- `findStoreExistingValues(array $requestValues, string $context = 'backend'): ?array` — return an existing row matching the request to convert a `store` into an idempotent upsert. Returning `null` keeps the default insert behavior.
- `afterStore(object $result, array $values = []): void`
- `afterUpdate(int|string $id, object $result, array $values = []): void`
- `afterDelete(int|string $id, object $result, array $values = []): void`

The base implementations are empty no-ops, so override only what you need.

### Singleton resources

Set `singletonRecordId(): int|string|null` to a non-empty value when a resource is supposed to manage exactly one row (e.g. site configuration). `isSingleton()` returns true automatically. Pair this with `pageSchema()->disable(['list', 'view', 'delete'])` (or similar) so the backend skips listing pages, and rely on `customBackendPages()` if the create/edit flow needs to be custom.

`class/App/Resources/Support/SingletonResource.php` shows the pattern used by the framework.

### Minimal Resource example

```php
<?php

namespace App\Resources;

use Wonder\App\Resource;
use Wonder\App\ResourceSchema\ApiSchema;
use Wonder\App\ResourceSchema\FormInput;
use Wonder\App\ResourceSchema\NavigationSchema;
use Wonder\App\ResourceSchema\PageSchema;
use Wonder\App\ResourceSchema\PermissionSchema;
use Wonder\App\ResourceSchema\TableColumn;
use Wonder\App\ResourceSchema\TableLayoutSchema;
use Wonder\Elements\Components\Card;
use Wonder\Elements\Form\Form;

final class ProjectResource extends Resource
{
    public static string $model = \App\Models\Project::class;

    public static string $orderColumn    = 'position';
    public static string $orderDirection = 'ASC';

    public static function path(): string { return 'projects'; }
    public static function icon(): string { return 'bi bi-folder'; }

    public static function textSchema(): array
    {
        return [
            'label'        => 'progetto',
            'plural_label' => 'progetti',
        ];
    }

    public static function labelSchema(): array
    {
        return [
            'name'        => 'Nome',
            'description' => 'Descrizione',
            'cover'       => 'Copertina',
            'visible'     => 'Stato',
        ];
    }

    public static function formSchema(): array
    {
        return [
            FormInput::key('name')->text()->required(),
            FormInput::key('description')->textarea(),
            FormInput::key('cover')->inputFileDragDrop('image'),
            FormInput::key('visible')->select([
                'true'  => 'Visibile',
                'false' => 'Nascosto',
            ])->value('true')->required(),
        ];
    }

    public static function formLayoutSchema(): ?Form
    {
        return (new Form)->components([
            (new Card)->components([
                static::getInput('name')->columnSpan(2),
                static::getInput('description')->columnSpan(2),
                static::getInput('cover')->columnSpan(2),
            ])->columns(2)->columnSpan(2),

            (new Card)->components([
                static::getInput('visible'),
            ])->columns(1)->columnSpan(1),
        ])->columns(3);
    }

    public static function tableSchema(): array
    {
        return [
            TableColumn::key('name')->text()->link('edit'),
            TableColumn::key('visible')->badge()->function('visible', 'id', 'automaticResize')->size('little'),
            TableColumn::key('actions')->button()->actions(['edit', 'delete']),
        ];
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Lista '.static::pluralLabel())
            ->results()
            ->buttonAdd('Aggiungi '.static::label())
            ->filters();
    }

    public static function apiSchema(): ApiSchema
    {
        return ApiSchema::for(static::class)
            ->fields('index', ['id', 'slug', 'name', 'description', 'visible']);
    }

    public static function permissionSchema(): PermissionSchema
    {
        return PermissionSchema::for(static::class)
            ->backendCrud(['admin', 'administrator'])
            ->apiCrud(['admin', 'administrator']);
    }

    public static function navigationSchema(): NavigationSchema
    {
        return NavigationSchema::for(static::class)
            ->section('Contenuti', 'content', 'bi-collection')
            ->title('Progetti')
            ->order(30)
            ->authority(['admin', 'administrator']);
    }
}
```

## `CustomPageSchema` (non-CRUD backend pages)

`class/App/PageSchema/CustomPageSchema.php` is the abstract base for backend pages that do not fit the Resource CRUD shape (e.g. login, password change, account profile, file upload dashboards).

When to use it instead of a Resource with custom routes:

- Use a **Resource with `customBackendPages()`** when the page is still bound to a single Model and conceptually belongs to that resource's URL space.
- Use **`CustomPageSchema`** when the page is cross-cutting (auth flows, settings dashboards, batch tools) and is not tied to one Model.

Minimal subclass:

```php
<?php

namespace App\PageSchema;

use Wonder\App\PageSchema\CustomPageSchema;
use Wonder\App\ResourceSchema\FormInput;

final class ContactPageSchema extends CustomPageSchema
{
    public static function labelSchema(): array
    {
        return [
            'name'    => 'Nome',
            'email'   => 'Email',
            'message' => 'Messaggio',
        ];
    }

    public static function contactFormSchema(): array
    {
        return static::applyLabelSchema([
            'name'    => FormInput::key('name')->text()->required(),
            'email'   => FormInput::key('email')->email()->required(),
            'message' => FormInput::key('message')->textarea()->required(),
        ]);
    }
}
```

`applyLabelSchema()` walks the returned schema and applies the label from `labelSchema()` to every field that does not already declare one. See `class/App/PageSchema/AccountPageSchema.php` and `class/App/PageSchema/CorporateDataPageSchema.php` for full examples.

Routes for a custom page schema are wired manually in the project's route files (custom backend route + handler). The schema only defines the form inputs and labels — it does not register routes by itself.

## Registration and discovery

- **ModelRegistry precedence**: framework core (`class/App/Models`) → enabled modules → site `app/Models` → site `custom/class/Models`. Full detail in [`architecture.md`](architecture.md).
- **ResourceRegistry precedence**: same layering plus configured resource lists from config files. Full detail in [`architecture.md`](architecture.md).
- Resource priority: site / custom definitions can override lower-priority ones.

After adding a Model or Resource, run `composer dump-autoload` from the project that owns the class file. The registries discover classes via PSR-4, so namespace and directory must match.

## Generated routing

`ResourceRouteRegistrar` iterates `ResourceRegistry::all()` and emits backend and API routes for every registered Resource:

- Backend routes (`Route::name('resource.<slug>.*')`): one route per action key in `pageSchema()->get('pages')` (`list`, `create`, `store`, `view`, `edit`, `update`, `delete`). Each route is permitted against the matching `permissionSchema()->get('backend')[<action>]` authority list.
- API routes (`Route::name('api.resource.<slug>.*')`): one route per action key in `apiSchema()` (`index`, `store`, `show`, `update`, `destroy`). Permitted against `permissionSchema()->get('api')[<action>]`.

The path prefix comes from `Resource::path()` (or `Model::$folder` as fallback). The slug used in route names is the slugified path.

When a resource declares an action in `customBackendPages()`, the registrar skips the default route for that action so the resource's own `registerBackendRoutes()` can register a replacement.

## Common gotchas

- Run `composer dump-autoload` whenever you add, move, or rename a Model / Resource. Otherwise the registry will not pick it up.
- Keep namespace and directory in sync with the PSR-4 root declared in the owning `composer.json`. A mismatch silently breaks discovery.
- Schema methods are static and called by the registrar lazily — do not assume the resource is "constructed". `static::class`, `static::path()`, `static::modelClass()` are the only safe references.
- `tableSchema()` is the SQL declaration; `dataSchema()` is the data declaration. They are not the same thing. If you only override one, the other is either empty or out of sync — both must be consistent, ideally with `tableSchema()` calling `sqlColumnsFromDataSchema(...)`.
- `static::$defaultCondition = ['deleted' => 'false']` is applied automatically. A query that needs to see soft-deleted rows must override the condition explicitly or pass a condition that already contains `deleted`.
- Repeater related rows must reference the parent record. Calling `syncRepeaterRelations($parentId, ...)` with a missing or zero `$parentId` is a no-op; the rows will be silently dropped. Ensure the parent is created (or `$parentId` is resolved) before syncing.
- Permission keys referenced in `Resource::permissionSchema()` only exist once the builder registry at `app/config/app/permission.php` plus the module merge plus `custom/config/permissions.php` have run. Adding a new key requires updating that builder, not the resource alone.
- Backend form layouts use `static::getInput('field_name')`. The field must already exist in `formSchema()` — otherwise `getInput()` throws `RuntimeException("Input resource non trovato: ...")`.

## Cross-references

- Permissions → [`permissions-and-users.md`](permissions-and-users.md)
- Bootstrap, registries, route emission → [`architecture.md`](architecture.md)
- Validation, where commands run, gotchas → [`workflows.md`](workflows.md)
