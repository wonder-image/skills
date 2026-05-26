# Copy-Paste Examples for Wonder Sites

Reference scaffolds for the five most common starting points in a Wonder site. Patterns derived from `wonder-image/new-site` (`App\Models\Form\Form`, `App\Resources\Form\FormContactResource`, `custom/view/pages/frontend/contact.php`, `custom/config/permissions.php`, `lang/it/pages.json`). Replace the example slug (`Project`) with the real domain noun when copying.

For the API contract behind these scaffolds see [`wi-app/references/model-and-resource.md`](../../wi-app/references/model-and-resource.md). For the form-input hard rule see the [`FormInput` / `FormField` hard rule](../../wi-app/references/model-and-resource.md#forminput--formfield-hard-rule).

## 1. Skeleton Model + Resource

A site Model + Resource pair for a generic "Project" CRUD. Save the Model under `app/Models/Project.php` and the Resource under `app/Resources/ProjectResource.php`, then run `composer dump-autoload` and `php forge update --local` from the site root.

### `app/Models/Project.php`

```php
<?php

namespace App\Models;

use Wonder\App\Model;
use Wonder\Data\UploadSchema as Field;
use Wonder\Sql\TableSchema as Column;

final class Project extends Model
{
    public static string $table = 'project';
    public static string $folder = 'projects';
    public static string $icon = 'bi bi-collection';

    public static function tableSchema(): array
    {
        return [
            ...static::sqlColumnsFromDataSchema([
                'slug',
                'name',
                'description',
                'cover',
                'visible',
            ]),
            Column::key('position')->int()->null(true),
        ];
    }

    public static function dataSchema(): array
    {
        return [
            Field::key('slug')->text()->lower()->slug('name'),
            Field::key('name')->text()->sanitizeFirst(),
            Field::key('description')->text(),
            Field::key('cover')->upload()->image()->dir('/projects/cover/'),
            Field::key('visible')->text()->default('true'),
        ];
    }
}
```

### `app/Resources/ProjectResource.php`

```php
<?php

namespace App\Resources;

use Wonder\App\Resource;
use Wonder\App\ResourceSchema\ApiSchema;
use Wonder\App\ResourceSchema\FormInput;
use Wonder\App\ResourceSchema\NavigationSchema;
use Wonder\App\ResourceSchema\PermissionSchema;
use Wonder\App\ResourceSchema\TableColumn;
use Wonder\App\ResourceSchema\TableLayoutSchema;

final class ProjectResource extends Resource
{
    public static string $model = \App\Models\Project::class;

    public static string $orderColumn = 'position';
    public static string $orderDirection = 'ASC';

    public static function textSchema(): array
    {
        return [
            'label' => 'progetto',
            'plural_label' => 'progetti',
            'article' => 'il',
            'this' => 'questo',
        ];
    }

    public static function labelSchema(): array
    {
        return [
            'name' => 'Nome',
            'description' => 'Descrizione',
            'cover' => 'Copertina',
            'visible' => 'Visibilità',
        ];
    }

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

    public static function tableSchema(): array
    {
        return [
            TableColumn::key('cover')->image()->size('little'),
            TableColumn::key('name')->text()->link('edit'),
            TableColumn::key('visible')->badge()->size('little'),
            TableColumn::key('actions')->button()->actions(['edit', 'delete']),
        ];
    }

    public static function tableLayoutSchema(): TableLayoutSchema
    {
        return TableLayoutSchema::for(static::class)
            ->title('Lista '.static::pluralLabel())
            ->results()
            ->buttonAdd('Aggiungi '.static::label())
            ->filters()
            ->searchFields(['name', 'description']);
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

    public static function apiSchema(): ApiSchema
    {
        return ApiSchema::for(static::class)
            ->fields('index', ['id', 'slug', 'name', 'description', 'cover', 'visible']);
    }
}
```

After saving both files:

```bash
composer dump-autoload
php forge update --local
```

`ResourceRouteRegistrar` will emit `/backend/projects/...` CRUD routes and `/api/projects/...` API routes automatically. `formSchema()` flows through the [`FormField` hard rule](../../wi-app/references/model-and-resource.md#forminput--formfield-hard-rule) — never replace it with raw HTML inputs.

## 2. Frontend page (`custom/view/pages/frontend/about.php`)

Companion route in `custom/routes/route.frontend.php`:

```php
Route::get('/about/', $ROOT.'/custom/view/pages/frontend/about.php')
    ->name('about');
```

Page file at `custom/view/pages/frontend/about.php`:

```php
<?php

    $SEO->title       = __t('pages.about.seo.title');
    $SEO->description = __t('pages.about.seo.description');
    $SEO->url         = __r('about');
    $SEO->breadcrumb  = [];

    \Wonder\View\View::layout('frontend.main');

?>

<section class="intro bg-light py-5">
    <div class="container">
        <h1 class="title-big"><?= __t('pages.about.content.hero.title') ?></h1>
        <p class="text mt-3"><?= __t('pages.about.content.hero.subtitle') ?></p>

        <a href="<?= __r('contact') ?>" class="wi-btn wi-btn-primary mt-4">
            <?= __t('pages.about.content.hero.cta') ?>
        </a>
    </div>
</section>

<?= \Wonder\View\View::component('frontend.sections.contact-form') ?>

<?php \Wonder\View\View::end(); ?>
```

Conventions exercised by this template:

- `\Wonder\View\View::layout('frontend.main')` + `\Wonder\View\View::end()` — required open/close.
- `__t(...)` for every visible string. Keys mirror the JSON shape (`pages.about.content.hero.title`).
- `__r('about')`, `__r('contact')` — URLs from named routes (preferred over `__u(...)`).
- `wi-btn wi-btn-primary` — lib classes consumed as-is (see [`style-and-lib.md`](style-and-lib.md)). Do not invent new `.wi-*` names; use existing utility classes (`container`, `py-5`, `mt-3`, `mt-4`) instead of bespoke CSS.
- Reuses `frontend.sections.contact-form` via `View::component(...)` instead of duplicating markup.

## 3. `lang/it/pages.json`

```json
{
    "home" : {
        "seo" : {
            "title" : "{{society_name}}",
            "description" : "{{legal_name}}"
        },
        "content" : {
            "hero" : {
                "title" : "Chi siamo",
                "subtitle" : "{{society_name}}"
            }
        }
    },
    "about" : {
        "seo" : {
            "title" : "Chi siamo - {{society_name}}",
            "description" : "La storia di {{society_name}} e il nostro approccio."
        },
        "content" : {
            "hero" : {
                "title" : "Chi siamo",
                "subtitle" : "Quello che facciamo, perché lo facciamo.",
                "cta" : "Contattaci"
            }
        }
    },
    "contact" : {
        "seo" : {
            "title" : "Contatti - {{society_name}}",
            "description" : "Tutti i contatti di {{society_name}}"
        },
        "content" : {
            "hero" : {
                "title" : "Contatti"
            }
        }
    }
}
```

Conventions exercised:

- Top-level keys (`home`, `about`, `contact`) match the leading segment of `__t("about.content.hero.title")`.
- `{{society_name}}`, `{{legal_name}}`, etc. — placeholders resolved at render time.
- Mirror the same structure under `lang/en/pages.json` (and any other locale registered in `custom/config/lang.php`). New JSON files (`lang/it/projects.json`, `lang/it/blog.json`, …) are picked up automatically.

## 4. `custom/config/permissions.php` — extend the builder registry

The framework's `app/config/app/permission.php` runs first (`Permissions::reset()->addArea(...)->addPermission(...)`), then modules merge, **then** `custom/config/permissions.php` runs against the same in-memory registry. Do **not** call `Permissions::reset()` here — it would erase the framework + module state.

Example: add a `segretaria` backend role plus an extra `frontend` permission, then reference the new key from a Resource.

```php
<?php

use Wonder\App\Permission\{Permissions, Permission, Area};

/**
 * Estensione site-side della registry.
 *
 * `Permissions::reset()` è già stato chiamato nel framework — qui si
 * aggiungono soltanto aree / permessi / route specifici del sito.
 */

Permissions::addPermission(
    Permission::make('segretaria', 'backend')
        ->name('Segretaria')
        ->icon("<i class='bi bi-clipboard-check'></i>")
        ->bg('bg-info')
        ->tx('text-white')
        ->color('info')
        ->creator(['admin', 'administrator'])
);

Permissions::addPermission(
    Permission::make('public_form', 'frontend')
        ->name('Form pubblico')
        ->creator(['admin'])
);

Permissions::area('backend')
    ->route('projects-readonly', 'resource.projects.list')
    ->route('projects-readonly', 'resource.projects.view');
```

Then from a Resource, reference the new authority:

```php
public static function permissionSchema(): PermissionSchema
{
    return PermissionSchema::for(static::class)
        ->backend(['list', 'view'], ['admin', 'administrator', 'segretaria'])
        ->backendCrud(['admin', 'administrator'])
        ->apiCrud(['admin', 'administrator']);
}
```

Full permission builder API and the `$PERMITS` export flow live in [`wi-app/references/permissions-and-users.md`](../../wi-app/references/permissions-and-users.md).

## 5. `custom/config/modules.php` — enable an external Composer module

`custom/config/modules.php` must return an array keyed by module slug (`wonder-image/<slug>`). `Wonder\App\Module\StateRepository` reads it; missing file = no modules enabled. Two equivalent shapes per entry:

```php
<?php

return [

    // Shape A — boolean shortcut.
    'wonder-image/blog' => true,

    // Shape B — explicit enabled flag plus per-module config.
    'wonder-image/newsletter' => [
        'enabled' => true,
        'config' => [
            'sender_email' => 'noreply@example.com',
            'list_id'      => 'main',
        ],
    ],

    // Disabled module — kept in the file for visibility.
    'wonder-image/shop' => [
        'enabled' => false,
        'config'  => [],
    ],

];
```

Notes:

- The package must be installed first (`composer require wonder-image/blog`). The file only toggles state; it does not install.
- Per-module `config` is exposed via `\Wonder\App\Module\StateRepository::config('wonder-image/<slug>')`.
- After editing this file, re-run `php forge update --local` so route / model / resource registration picks up the new state.
- Prefer this over copy-pasting module code into `custom/` — see [`workflows.md`](workflows.md) on module integration.
