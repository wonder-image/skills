# Wonder Image App Workflows

## Decide Where the Change Belongs

- Use the **framework** when changing framework behavior, bootstrap, registries, route generation, console command source, shared resources, or architecture conventions.
- Use a **site** when changing project-specific models, resources, config, templates, content, or enabled-module state.
- Use an external **module** package when the feature is reusable and should not be embedded into the framework.

## Preferred Placement for New Work

- Prefer `class/App/*` PSR-4 classes for new logic.
- Use `app/*` when you must interact with legacy runtime handlers, bootstraps, routes, or helpers that the new layer still delegates to.
- For backend forms:
  - put SQL structure in `Model::tableSchema()`
  - put data transformation and persistence rules in `Model::dataSchema()`
  - put backend inputs in `Resource::formSchema()`
- Use `CustomPageSchema` for non-CRUD backend pages.
- Use `FormInput::repeater()`, `RepeaterColumn`, and `Wonder\App\Support\Repeater` for repeatable rows.
- **All form inputs go through `FormField`**, on the frontend Wonder theme and the backend Bootstrap theme alike. Declare with `FormInput::key(...)` / `RepeaterColumn::key(...)` / `FormSchema::for(...)`; render with `FormField::render($theme)`. Never emit raw `<input>` / `<select>` / `<textarea>` HTML and never wrap input rendering in custom functions that bypass the schema. Missing input types are added at the framework layer (`FormField` helper + `FormFieldElementFactory` mapping + Wonder/Bootstrap renderer under `class/Themes/*`), not patched into the call site. Full rule in [`model-and-resource.md`](model-and-resource.md#forminput--formfield-hard-rule).

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

## Repo-Specific Gotchas

- This repo has no standalone `forge` executable at package root.
- `php forge ...` examples in docs are integration commands for the site, not for this package root.
- Some fixes require testing against a sibling site because the real runtime lives there.
- `app/config/app/table.php` still loads PHP files from `app/build/table/`; do not add new files there.
- `SortableInput` is deprecated. Keep it only for compatibility.
- The canonical module pattern is Composer-package based, not copy-paste under `custom/...`.

## Documentation Expectations

- When changing architecture, rendering flow, bootstrap/runtime setup, layout structure, or developer-facing conventions, update the related GitBook docs under `docs/app/*` in the same work.
- If the change is narrow and internal, keep docs unchanged unless behavior or conventions actually moved.
