# Security and deploy in a site

Read this when site code handles visitor input (forms, requests, emails,
redirects, exports), writes hand-made forms or POST handlers, adds tracking,
edits `.htaccess` or `.github/workflows/deploy.yml`, or runs anything that
changes the database outside `php forge update`.

These rules are the site side of contracts the framework owns. The framework
side (owner classes, alert codes, what the core already does) lives in
[`wi-app/references/security.md`](../../wi-app/references/security.md): read it
before reimplementing anything listed here. Canonical prose: `docs/app/piattaforma/sicurezza.md`
and `docs/app/concetti/form/csrf.md` in `wonder-image/app`.

## Visitor data in backend pages and emails

- A custom backend page that prints visitor-written values (requests, contacts,
  orders, form submissions) escapes every value with `e()`; use `e_br()` only for
  free text whose line breaks must survive (a message body). Never echo a raw
  column, even "just for the admin".
- Emails built from a public form escape the same way before the values go
  into the template. A URL the visitor supplied is printed only after checking
  it is absolute `http`/`https` with a host and no credentials; anything else
  becomes an empty value or `#`.
- Reference implementation (site-local, not a framework API):
  `wonder-image/ecommerce-site` — `custom/view/pages/backend/request/show.php`
  for the page, `RequestResource` (`app/Resources/Site/RequestResource.php`) for
  the email values, `App\Support\SafeUrl::http()` (`app/Support/SafeUrl.php`)
  for the URL check. Copy the pattern into the site's own `app/Support/`; do
  not import it from another site.

## Hand-written forms and POST handlers

- Forms built with the framework Elements (`Form`, `Button::post()`, POST
  `Dropdown` items, `Modal::form()`) already carry the token. A hand-written
  `<form method="post">` prints `<?= \Wonder\Http\Csrf::field() ?>`; a `fetch`
  from site JS sends the `X-WI-CSRF` header read from `<meta name="wi-csrf">`
  (from `wonder-image` lib ≥ 2.1.2-alpha.31 the lib's own `fetch` /
  `ajaxRequest` add it).
- Frontend auth and account forms of the framework post their token as
  `csrf_token` (`AuthSession`, logout, impersonation banner): when overriding
  their views, keep that field and do not add `_csrf` beside it.
- The handler verifies before writing: declare `->csrf()` on the route in
  `custom/routes/` (the dispatcher answers 403 and never includes the handler),
  or call `Csrf::verify()` at the top of a page handler and stop on failure.
  Routes authenticated only by a Bearer token do not declare `->csrf()`.
- On a failed check show notification `929` (`ResourcePageController::CSRF_ALERT`;
  `Csrf::ALERT` once wonder-image/app#128 lands) and re-render the form with
  the values the user typed (with #128: `Csrf::flashFailure()` /
  `Csrf::oldInput()`). Never show a message containing "CSRF" or "token".
- Never change state on GET (logout, delete, "connect", "send again"): show a
  confirmation page whose form POSTs.

## Redirects

A `continue` / `return` / `redirect` value from the request goes through
`Wonder\Http\SafeRedirect::fromRequest($value, $fallback)` (or `fromBase64()`
for the backend `?redirect=` form). Never `header('Location: ' . $_GET[...])`.

## Exports

A backend list that exports visitor-written data (contacts, requests, public
forms) declares `downloadAsText()` on its `TableLayoutSchema`, so cells stay
text in Excel and CSV formula prefixes are neutralized. Do not turn off the
default formula protection (`downloadFormulaSafe(false)`) on such a list.

## Tracking and consent

All tracking goes through Google Tag Manager. The framework writes Consent Mode
defaults (every storage denied) before the GTM loader and does not emit the
Meta Pixel: add the Pixel, and any other tracker, as a GTM tag bound to
consent. Do not paste tracker snippets into `custom/view/` layouts. Docs:
`docs/app/servizi/google-tag-manager/configurazione.md`.

## `.htaccess`

Forge owns the `PERFORMANCE`, `SECURITY` and `UPLOAD` blocks between
`# WONDER <NAME> START` and `# WONDER <NAME> END` markers (root `.htaccess` and
`assets/upload/.htaccess`) and
rewrites them on `php forge update`. Never edit inside a block: changes are
lost. Site-specific rules go outside the markers, where Forge leaves them
alone. Files the browser must load from a package live under
`vendor/<vendor>/<package>/resources/assets/`; every other `vendor/` path is
denied.

## Database backups

- `php forge update` already backs up a non-empty database before migrations
  (`storage/backup`, last 5 kept) and stops when the backup fails. Never pass
  `--no-backup` (or `"backup": false` on the API trigger) for production.
- Before a manual destructive step (bulk delete, data fix, import over
  existing rows) run `php forge db:backup` and report the file it wrote.
- `storage/` must not be reachable over HTTP; on nginx or with
  `AllowOverride None` the server needs its own deny rule.

## Deploy exclusions (proposal)

`wonder-image/ecommerce-site` excludes, on top of the scaffold list in
`.github/workflows/deploy.yml`: `state/**`, `**/*.log`, `skills-lock.json`,
`tests/**`. The scaffold (`wonder-image/new-site`) does not have these lines
yet. When you touch `deploy.yml` in a site that has those paths, propose adding
them to the `exclude:` list; do not add them silently, and do not remove
existing entries.
