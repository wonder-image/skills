# Security contracts in `wonder-image/app`

Read this before touching request handling, sessions, cookies, uploads,
exports, redirects, SQL helpers, logging or API auth. Each rule names the class
that owns the behavior: reuse it, never re-implement it in a handler or a
module. The canonical prose lives in the app docs linked per section; this file
lists the contracts an agent must not break.

Status: everything below is merged on `wonder-image/app` main. Client-side
parts marked "lib ≥ 2.1.2-alpha.31" need `wonder-image` lib ≥ 2.1.2-alpha.31
in the site (published on npm, dist-tag `alpha`; `extra.wonder.lib` of the app
already requires it).

## CSRF

Owner: `Wonder\Http\Csrf` (`class/Http/Csrf.php`). Docs:
`docs/app/concetti/form/csrf.md`.

- One token per session. API: `token()`, `field()`, `verify()`, `active()`,
  `fieldFor($method)`, `legacyFormField()`. Field `_csrf`, header
  `X-WI-CSRF`, `<meta name="wi-csrf">` in the base layouts. From `wonder-image`
  lib ≥ 2.1.2-alpha.31 the lib sends the header from `ajaxRequest`, `fetch`,
  `ApiClient`, DataTables and Editor.js.
- Frontend auth forms (`app/view/pages/frontend/auth/*`) post the field
  `csrf_token` with `AuthSession::csrfToken()`, verified by
  `AuthSession::verify()` (same session token as `Csrf`); the account logout
  posts `csrf_token` with the page's `logout_token`, and the impersonation
  banner posts `csrf_token` with `Impersonation::csrfToken()`. Do not rename
  those fields and do not add `_csrf` beside them.
- Automatic emission (both `Form` renderers, `Button::post()`, POST `Dropdown`
  items, `Modal::form()`, the Resource form page) goes through
  `Csrf::fieldFor()`: never for GET, never without an active session or with a
  `public` cache limiter. Hand-written `<form>` tags print `Csrf::field()`.
- Routes opt into verification with `->csrf()` on a `RouteDefinition` or a
  `RouteGroup` (default `false`); `->csrf(false)` takes one route out of a
  group. `RouteDispatcher` calls `Wonder\Http\CsrfGuard::enforce($route)`
  after `authorizeUser()` and before the handler: methods other than
  GET/HEAD/OPTIONS without a valid token throw `ForbiddenHttpException`
  (403 JSON or the HTML error page) and the handler is not included. The 403
  text is `Csrf::failureMessage()`.
- Every `/api/backend/*` route is under `->csrf()` (`app/config/routes/route.api.php`)
  except `/api/backend/alert/`. A new backend API route is both `guarded()` and
  inside that group.
- Bearer routes (Resource API, tasks, `/api/app/update/`) and OAuth flows do
  not declare `->csrf()`: the browser never sends `Authorization` by itself.
  When the same endpoint is reached without bearer from a page (session
  access), `Endpoint` itself requires same origin and `Csrf::verify()`.
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
- User-facing failure text is notification `929` ("La pagina è scaduta…"),
  never a message containing "CSRF" or "token"; re-render the form with the
  values the user typed. Helpers: `Csrf::ALERT` (929; same value as
  `ResourcePageController::CSRF_ALERT`), `Csrf::failureMessage()` (reads
  `notifications.929`), `Csrf::oldInput($input, $extra)` (empties to `''` the
  token, the fields of `Csrf::SENSITIVE` and every key starting with `password`), `Csrf::flashFailure()`.
  `AccountController::renderCsrfFailure()` (protected) and
  `AuthController::renderCsrfFailure()` re-render the page with the typed
  values; a module controller that extends `AccountController` overrides the
  protected one (customer-account: `CustomerAccountController::renderCsrfFailure()`
  reopens the address editor with alert 929 and writes nothing).
- Do not add per-feature tokens: `AuthSession` delegates to `Csrf`;
  `_contact_csrf` was removed from the customer-account module, whose forms
  now use `_csrf`: do not bring it back in the core or in sites; `Impersonation` keeps its scoped token.

## Cookies, sessions and proxies

Docs: `docs/app/concetti/form/csrf.md#cookie`, `docs/app/piattaforma/multi-ambiente.md`.

- `Wonder\Http\Cookie` is the only caller of `setcookie`: SameSite=Lax,
  HttpOnly, Secure on HTTPS. Session cookie options come from
  `Cookie::sessionOptions()` in `app/config/app/ini.php`.
- Forwarded headers count only with `APP_TRUST_PROXY=true` and are read only
  by these classes: `X-Forwarded-For` → `Wonder\Http\ClientIp`;
  `X-Forwarded-Proto` → `Cookie::isSecure()` and `Wonder\Http\UrlParser`;
  `X-Forwarded-Scheme` / `-Ssl` → `UrlParser`. Never read them directly.

## Authentication helpers

Docs: `docs/app/concetti/utenti/auth-frontend.md`, `docs/app/piattaforma/api.md#token`.

- Passwords: argon2id through `app/function/string/password.php`; rehash on
  login with `passwordNeedsRehash`.
- Login throttling: `Wonder\Auth\LoginThrottle` (per-login and per-IP buckets).
  Enumeration-safe responses use the uniform code 925; do not add a message
  that tells "user exists" from "wrong password".
- One-time tokens (reset, verification, magic links): `Wonder\Auth\OneTimeToken`.
- Changing a user's email through `user()` (modify) sets `email_verified = 0`
  and `email_verified_at = NULL` in the same UPDATE, overriding a posted
  `email_verified`, then revokes the open verification links
  (`userEmailVerificationRevokeOpenTokens()`). Code that trusts an address
  checks `isUserEmailVerified()`, never the email alone.
- reCAPTCHA: `Wonder\App\Security\RecaptchaGuard`.
- Remember-me is configured on the profile (`AuthProfile::rememberMe()`).
- Same-origin checks for session-bound endpoints: `Wonder\Http\SameOrigin`.
- API bearer and page-token rules are the Core Rule in `SKILL.md`; Resource API
  access checks live in `Wonder\Api\Support\ResourceApiAccess`.

## Rate limits

Two primitives, split by owner:

- `Wonder\Auth\LoginThrottle` (core) covers authentication only.
- Commercial attempts (orders, coupons, guest checks) use
  `Wonder\Plugin\Gestionale\Support\Throttle` from the gestionale module:
  `attempt($bucket, $key, $limit, $window)` is one atomic UPSERT on
  `gst_throttles` (Model `System\Throttle`, created by `php forge update`)
  with a fixed window; `allowed()` / `hit()` keep file counters under
  `storage/gestionale/throttle`. Keys are hashed, a limit of 0 or an empty key
  never blocks, and a store error lets the request through (logged as
  `gestionale.throttle`). `Throttle::ip()` is `Wonder\Http\ClientIp::resolve()`,
  so `APP_TRUST_PROXY` decides the address. Each module keeps its own buckets
  and limits (ecommerce: guest orders in config, coupons, declined and customer
  orders as constants in code); do not add a third throttle store.
- Ecommerce calls the gestionale class directly; `Wonder\Plugin\Ecommerce\Support\Throttle`
  is a deprecated `class_alias` that breaks under
  `composer dump-autoload --classmap-authoritative` and goes away in 0.3.0.

## Orders of a returning customer (gestionale + ecommerce)

- `Wonder\Plugin\Gestionale\Support\Orders\OrderOwner::claim($userId, $customerId, $email)`
  attaches unlinked orders with that email to the account; it never takes an
  order in stage `cart`.
- Ecommerce calls it only through `Wonder\Plugin\Ecommerce\Frontend\Auth\OrderClaim::run()`,
  on pages that show orders (account overview, list and detail; checkout
  index, completed and return), never on actions. It runs only for a logged-in
  frontend user with the `client` authority, not deleted, with a verified email
  and a customer card: staff accounts are never claimed. Errors are logged as
  `ecommerce.account.order_claim` and do not stop the page.
- Express checkout with the email of an existing account creates the order
  unlinked (`user_id` / `customer_id` 0) instead of refusing it
  (`GuestCheckout::account(..., $express = true)`); reCAPTCHA on start and
  `checkout.guest_order_limit` still apply, and `AuthLog` records
  `guest_checkout_existing_unlinked` with an email hash. The wallet email is
  not verified; the order reaches the account later, through the claim
  above. Accepted risk: an express order paid by someone else with that email
  can end up in that account.

## Authorization of backend list mutations

`Wonder\Backend\Table\TableAuthorization` authorizes every list mutation
endpoint (`change/boolean`, `active`, `visible`, `delete`, `delete-icon`,
`move`, `file/delete`, `file/move`, `authority`) before any SQL. Do not add a list mutation endpoint that skips it.

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
`class/Console/Commands/Build.php` maintains the `PERFORMANCE`, `SECURITY` and
`UPLOAD` blocks between `# WONDER <NAME> START` and `# WONDER <NAME> END`
markers; the rest of the file is left alone. Package assets served to the browser must live under
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

A client-chosen connection goes through
`Wonder\App\Support\ApiRequest::selectDatabase()`: it accepts a configured
alias of `$DB->database` or, from backend lists, the real name
(`Table::$database`) mapped back to its alias; anything else, and
`information_schema` always, answers 422. The match is case-exact: do not
loosen it.

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
