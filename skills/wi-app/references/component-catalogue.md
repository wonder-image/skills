# Component catalogue (`docs/components`)

Every shared Element of `wonder-image/app` is documented by a card in `docs/components/<category>/<slug>.php`, rendered as a live catalogue (code to copy + preview in the chosen theme). Read this before adding or changing a component or a public component API.

## Hard rule

A new component, or a new public API on an existing one, is not complete without its card (or an example added to the existing card). `php tests/Docs/CatalogRenderTest.php` must pass: it renders every example in every theme where the component is available and fails on an exception or empty HTML.

Canonical guide: `docs/components/README.md` in the app repo. Concept page: `docs/app/concetti/componenti/catalogo.md`.

## Where cards live

- The folder is the category: `form/` (`Elements/Form/Components/*`, `Form`), `components/` (`Elements/Components/*`), `media/` (`Elements/Media/*`), `charts/` (`Elements/Charts/*`).
- Inside a category, `->group('...')` picks a group declared in `Wonder\Docs\Catalog::defaultCategories()`; `->order(n)` sorts within it.
- Slugs are the kebab-case short class name and must be unique; when two Elements share a short name (form `Button` vs component `Button`), one card declares `->slug('form-button')`.

## Card format

A card file `return`s a `Wonder\Docs\ComponentDoc`:

- `ComponentDoc::for(Class::class)` plus `title()`, `group()`, `order()`, `tags()`, `description()` (plain text, backticks become `<code>`, blank line = paragraph; Italian).
- `uses(...)` lists classes the examples name; the matching `use` line is prepended to the shown and executed code, so examples never repeat `use`.
- `example('Title', <<<'PHP' ... PHP, 'description')` or an `Example::make()` with `themes()` and `height()`. The code is a nowdoc **without `<?php` and without `return`**; its last statement is the expression that yields the Element (or an array of Elements, or an HTML string). What the page shows is what runs (`Snippet` + `ExampleRunner`).
- `Example::themes('bootstrap')` restricts an example to one theme when it uses an API that exists only there; `Example::height(px)` gives previews that open over the content (dropdowns, calendars, modals) a minimum height.
- `note('wonder'|'bootstrap', '...')` documents a real behaviour difference between themes.
- `docs()` links the GitBook page, `related()` other cards by slug, `deprecated()` flags what new code must not use.
- Example images: `Wonder\Docs\Assets::url('paesaggio-1.jpg')` and add `Assets::class` to `uses()`.

## Theme availability is computed, never written

`ThemeSupport` asks `Themes\Resolver` whether a renderer exists in each theme. Do not hand-write which themes a component supports. `unsupported('theme', 'reason')` is the exception, for a renderer that exists but does not truly render the component. If availability looks wrong, fix the missing renderer (see the "Adding a new Component" steps in `AGENTS.md`), not the card.

## Opening the catalogue

- In a site backend: **Dev → Componenti** (`/backend/app/docs/components/`, `admin` only).
- From the package root, without a site: `npm install` once (brings `wonder-image/lib` into `node_modules`), then `composer docs` or `php bin/docs.php [host:port]` and open `http://127.0.0.1:8090/`.

Each preview is an iframe loading only the assets of its theme; Wonder and Bootstrap stylesheets must never share a page.

## Related contracts

- The npm constraint on `wonder-image` in the package `package.json` must equal `extra.wonder.lib` in `composer.json` (a test checks it). The minimum lib version is declared only in `extra.wonder.lib`.
- CSS or JS that a component prints once per page, however many instances there are, goes through `Themes\Support\PageAssets::once()`, not through ad-hoc flags.
- `Code` (code block with `Support\Code\Highlighter` and copy button) and `Preview` (iframe with selectable sources and light/dark) are shared Elements for both themes; their `data-wi-code` / `data-wi-copy` and `data-wi-preview*` contracts are owned by `wonder-image/lib`.
- Design tokens shown in previews come from `Wonder\View\CssTokens`, the same template as `cssRoot()`.
