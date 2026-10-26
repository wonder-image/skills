# Security contracts in `wonder-image/app`

Read this before touching request handling, sessions, cookies, uploads,
exports, redirects, SQL helpers, logging or API auth. Each rule names the class
that owns the behavior: reuse it, never re-implement it in a handler or a
module. The canonical prose lives in the app docs linked per section; this file
lists the contracts an agent must not break.

Status: everything below is merged on `wonder-image/app` main, except the
section "In arrivo".

## CSRF

Owner: `Wonder\Http\Csrf` (`class/Http/Csrf.php`). Docs:
`docs/app/concetti/form/csrf.md`.

- One token per session. API: `token()`, `field()`, `verify()`, `active()`,
  `fieldFor($method)`, `legacyFormField()`. Field `_csrf`, header
  `X-WI-CSRF`, `<meta name="wi-csrf">` in the base layouts. The lib sends the
  header from `ajaxRequest`, `fetch`, `ApiClient`, DataTables and Editor.js.
- Automatic emission (both `Form` renderers, `Button::post()`, POST `Dropdown`
  items, `Modal::form()`, the Resource form page) goes through
  `Csrf::fieldFor()`: never for GET, never without an active session or with a
  `public` cache limiter. Hand-written `<form>` tags print `Csrf::field()`.
- Routes opt into verification with `->csrf()` on a `RouteDefinition` or a
  `RouteGroup` (default `false`); `->csrf(false)` takes one route out of a
  group. `RouteDispatcher` calls `Wonder\Http\CsrfGuard::enforce($route)`
  after `authorizeUser()` and before the handler: methods other than
  GET/HEAD/OPTIONS without a valid token throw `ForbiddenHttpException`
  (403 JSON or the HTML error page) and the handler is not included.
- Every `/api/backend/*` route is under `->csrf()` (`app/config/routes/route.api.php`)
  except `/api/backend/alert/`. A new backend API route is both `guarded()` and
  inside that group.
- Bearer-only routes (Resource API, page token, tasks, `/api/app/update/`)
  and OAuth flows do not declare `->csrf()`: the browser never sends
  `Authorization` by itself.
- Core backend pages that write on POST verify first (Resource CRUD through
  `ResourcePageController`, users, account, login and password pages, bulk
  uploader, config files, legacy forms including `app/html/backend/index.php`,
  logout, Stripe webhook connect). The failure code is alert `929`
  (`ResourcePageController::CSRF_ALERT`), shown through `$ALERT` or
  `FlashAlert`; quick-create answers 403 JSON and the Stripe connect page 403. On login/password pages the
  CSRF check runs before the throttle and the user lookup.
- No action on GET: backend logout and Stripe "connect webhook" show a
  confirmation page whose form POSTs `_csrf`. A token in the query string never
  counts.
- Do not add per-feature tokens: `AuthSession` delegates to `Csrf`;
  `_contact_csrf` is legacy and `Impersonation` keeps its scoped token.

## Cookies, sessions and proxies

Docs: `docs/app/concetti/form/csrf.md#cookie`, `docs/app/piattaforma/multi-ambiente.md`.

- `Wonder\Http\Cookie` is the only caller of `setcookie`: SameSite=Lax,
  HttpOnly, Secure on HTTPS. Session cookie options come from
  `Cookie::sessionOptions()` in `app/config/app/ini.php`.
- Forwarded headers (`X-Forwarded-For`, `-Proto`, `-Scheme`, `-Ssl`) count only
  with `APP_TRUST_PROXY=true`, in one place each: `Cookie::isSecure()`,
  `Wonder\Http\ClientIp`, `Wonder\Http\UrlParser`. Never read them directly.

## Authentication helpers

Docs: `docs/app/concetti/utenti/auth-frontend.md`, `docs/app/piattaforma/api.md#token`.

- Passwords: argon2id through `app/function/string/password.php`; rehash on
  login with `passwordNeedsRehash`.
- Login throttling: `Wonder\Auth\LoginThrottle` (per-login and per-IP buckets).
  Enumeration-safe responses use the uniform code 925; do not add a message
  that tells "user exists" from "wrong password".
- One-time tokens (reset, verification, magic links): `Wonder\Auth\OneTimeToken`.
- reCAPTCHA: `Wonder\App\Security\RecaptchaGuard`.
- Remember-me is configured on the profile (`AuthProfile::rememberMe()`).
- Same-origin checks for session-bound endpoints: `Wonder\Http\SameOrigin`.
- API bearer and page-token rules are the Core Rule in `SKILL.md`; Resource API
  access checks live in `Wonder\Api\Support\ResourceApiAccess`.

## Authorization of backend list mutations

`Wonder\Backend\Table\TableAuthorization` authorizes `change/boolean`,
`active`, `visible`, `delete`, `move`, `file/delete`, `file/move` and
`authority` before any SQL. Do not add a list mutation endpoint that skips it.

## Redirects

Request-supplied targets (`continue`, backend `?redirect=` base64) go through
`Wonder\Http\SafeRedirect::fromRequest()` / `fromBase64()` (`isSafe()` for a
plain check). Never redirect to a raw request value.

## Uploads

Docs: `docs/app/piattaforma/sicurezza.md#upload`.

- `uploadFiles()` checks every file with
  `Wonder\App\Support\UploadValidator::checkFormat()`: extension whitelist
  (ALERT 921), then `finfo` MIME check (ALERT 927). `gif` and `svg` must be
  declared in `extensions`; `svgz` is always rejected.
- SVG content goes through `Wonder\App\Security\SvgSanitizer::sanitize()`
  (ALERT 931 when it cannot be cleaned).
- Upload folders are normalized with `Wonder\App\Support\UploadDir`.
- `assets/upload/.htaccess` carries the managed `WONDER UPLOAD` block (no script
  execution).

## Managed `.htaccess` blocks

Docs: `docs/app/piattaforma/sicurezza.md#blocchi-htaccess-gestiti-da-forge`.
`class/Console/Commands/Build.php` maintains `WONDER PERFORMANCE`,
`WONDER SECURITY` and `WONDER UPLOAD` between markers; the rest of the file is
left alone. Package assets served to the browser must live under
`vendor/<vendor>/<package>/resources/assets/` (static extensions only): every
other `vendor/` path is denied. PHP responses get the same headers from
`Wonder\Http\SecurityHeaders`.

## Exports

Docs: `docs/app/piattaforma/sicurezza.md#export-csv`.

- CSV cells that start with `=`, `+`, `-`, `@`, tab or CR are prefixed with an
  apostrophe. Opt-outs are explicit: `arrayToCsv(..., false)`,
  `sqlExport(..., false)`, `TableLayoutSchema::downloadFormulaSafe(false)`.
- Exports of visitor-written data use `TableLayoutSchema::downloadAsText()`
  (`Wonder\Backend\Support\SpreadsheetText`).

## SQL

Docs: `docs/app/piattaforma/sicurezza.md#sql`. Values go through
`Query::bind()` / `sqlBind()` or the connection escape; identifiers through
`Query::escapeIdentifier()`. `$order`, keys of array conditions and the other
raw fragments are written by code, never by the request (see the app
`AGENTS.md`).

## Errors, debug and logs

Docs: `docs/app/piattaforma/sicurezza.md#errori-500-e-request_id`, `#log`.

- `Debug::enabled()` (`APP_DEBUG`, otherwise the local environment) is the only switch for
  detailed errors. API 500s return a generic `response` plus `request_id` and
  `X-Request-Id` (`Wonder\Http\RequestId`); the failing SQL stays in
  `QueryException::query()`, never in the response.
- `Logger` writes only under `ROOT/storage/logs/` (0750 / 0640). Redact URIs and
  identifiers with `Wonder\App\LogRedactor` before logging.

## Database backups

Docs: `docs/app/piattaforma/installazione-e-deploy.md`.

- `php forge update` backs up a non-empty database before migrations
  (`Wonder\App\Support\DatabaseBackup`, `storage/backup`, last 5 kept) and stops
  if the backup fails. `--no-backup` (or `"backup": false` on the API trigger)
  skips it.
- `php forge db:backup` (`class/Console/Commands/DbBackup.php`, options
  `--keep=N`, `--path=DIR`) is the manual command.

## Payments

Every `StripeClient` of the plugin is built from `Stripe::clientConfig($key)`
(`class/Plugin/Stripe/Stripe.php`, used by `Connect` and `PaymentIntent`):
pinned API version, network retries (so POSTs get an Idempotency-Key) and the
shared HTTP client with shorter timeouts. Never `new StripeClient($key)` with
ad-hoc options.

## Analytics and consent

Docs: `docs/app/servizi/google-tag-manager/configurazione.md#consenso-e-datalayer`.
The frontend layout writes `gtag('consent', 'default', …)` with every storage
denied (Consent Mode) before the GTM loader, and pushes only an HMAC of the user
id (`Wonder\Frontend\Support\DataLayer`). The core does not emit the Meta Pixel:
it is a GTM tag subject to consent. Do not add trackers outside GTM.

## In arrivo

Not on main yet: do not document these as rules or rely on them in code.

- **Generic CSRF failure handling** (branch `fix/2026-10-4d-csrf-generico`, no
  PR yet): `Csrf::ALERT`, `Csrf::oldInput()`, `failureMessage()`,
  `flashFailure()` and an `AccountController::renderCsrfFailure()` extension
  point. Until it lands, use alert `929` as described above.
- **Removal of the legacy `modal()` helpers** (audit block 4, PR-B / PR-C): they
  are `@deprecated` aliases today; see the confirmation rule in `SKILL.md`.
- **Performance block** (audit block 5): open questions on `Geo::country()` and
  the `Api\Call` timeout. Nothing to apply yet.
