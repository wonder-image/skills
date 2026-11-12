---
name: wi-testing
description: |
  Write and run the plain-PHP tests of a Wonder site (`tests/<Name>Test.php`
  with `tests/bootstrap.php`, run by `.github/workflows/ci.yml`).
  TRIGGER when: adding or changing a test under `tests/`; a site change fixes a
  bug or touches visitor data, escaping, CSRF, routes, permissions or a
  Resource hook that must not break; the user asks to "add a test" / "testare".
  SKIP when: the repo is `wonder-image/app` (framework tests, use wi-app) or
  `wonder-image/lib`.
---

# Wonder site tests

A site test is a plain PHP script, `tests/<Name>Test.php`, that prints one line
per check and exits non-zero when any check fails. No PHPUnit, no database, no
HTTP server: CI runs every `tests/*Test.php` with `php` on each pull request
and before each deploy.

## Steps

1. **Bootstrap.** If `tests/bootstrap.php` is missing, copy
   [`references/bootstrap.php`](references/bootstrap.php) there. It loads the
   Composer autoload and the framework's pure helpers (`e()`, `e_br()`,
   `sanitize()`, `sanitizeEcho()`) and defines `check($name, fn)` and
   `summary()`. Needs `vendor/`: `composer install --no-scripts`.
   Done when `php tests/bootstrap.php` exits 0.
2. **Pick the seam.** Test the smallest piece that carries the behavior,
   in this order of preference:
   - a pure function or a `public static` method (e.g.
     `RequestResource::mailValues()`, `App\Support\SafeUrl::http()`); if the
     logic sits inside a hook that also sends mail or writes the DB, extract it
     into a static method first and call that from the hook;
   - the route registry (`Route::reset()`, register, read `Route::all()`) for
     routes, `csrf`, permits — see `tests/RequestSendRouteTest.php` in
     `wonder-image/ecommerce-site`;
   - a **source contract** (read the file with `file_get_contents` and assert
     the call is there) only for a view that needs `View::layout` or the DB,
     e.g. `show.php` must contain `e($REQUEST['email'])`.
   Done when you can name the input and the expected output of every check.
3. **Write the test.** One file per behavior, header comment with the run
   command and what it covers, `require __DIR__.'/bootstrap.php';`, one
   `check()` per case, `summary();` at the end. Check names describe the
   behavior in Italian, as in the existing tests. Hostile inputs for escaping
   and URLs: `<script>`, `onerror=`, quotes, `javascript:`, `data:`, `//host`,
   `user:pass@host`. If a check needs translations, initialise them with
   `LanguageContext::addLangPath(...)->setLang('it')` and
   `TranslationProvider::init()`.
4. **Red, then green.** Run the test against the code *without* the change
   (stash it or reason from the diff) and see it **red**; then with the change
   and see it green. A test that was never red proves nothing.
   Done when both runs are observed, or the report says why red was impossible.
5. **Run the suite.**
   `for t in tests/*Test.php; do php "$t" || exit 1; done`
   Done when every file ends with `0 falliti`.

## Report

List the test files added, what each covers, the red/green evidence, and what
stays manual (real mail delivery, full page rendering with the DB).
