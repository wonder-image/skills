---
name: wi-review
description: |
  Review a change in a Wonder site against the site rules before a pull
  request: placement, translations, URLs, escaping, CSRF, UI and lib usage,
  generated files, tests, runtime steps.
  TRIGGER when: the user asks to review, check or audit a diff, branch or PR
  of a site (`composer.json` requires `wonder-image/app`); before opening a
  PR on a site; "rivedi", "controlla le modifiche".
  SKIP when: the repo is `wonder-image/app` or `wonder-image/lib`.
---

# Wonder site review

The output is a list of **findings**. A finding is a rule broken by a
concrete line: file, line, rule, why it matters, the fix. Fix nothing unless
the user asks: the review reports.

## Steps

1. **Scope.** The diff the user names (a commit, a branch, a PR); default
   `git diff origin/main...HEAD`. List every changed file. Read `docs/ai/PRODUCT.md` if copy or
   UI changed, and `custom/config/lang.php` for the active languages.
2. **Checks.** Run the site's validation exactly as the "Validation" section
   of `AGENTS.md` lists it (`composer install --no-scripts` first if
   `vendor/` is missing). A red check is a blocking finding, reported against
   the source line that turned it red, not as a missing test.
3. **Rules.** Apply every rule below to the lines the diff adds or changes.
   Done when each (file, rule) pair is either a finding or clean. Problems
   already in a touched file go in a separate "pre-existing" list: they do
   not block this change.
4. **Report**, in this order: **Blocking** (security, red check, wrong
   placement, edited generated file) and **Should fix**, each finding with
   `path:line`, rule, why, fix; **Pre-existing**; **Checks run** with their
   result; **Developer still has to** (Forge commands, backend settings);
   **Clean** rule groups.

## Rules

**Placement, text, URLs, models** — apply every item of the
Non-Negotiable Rules in [`wi-site/SKILL.md`](../wi-site/SKILL.md#non-negotiable-rules)
and the Coding conventions and Generated files of the site's `AGENTS.md`.
With two or more active languages in `custom/config/lang.php`, a new key
must exist in each. Framework or lib changes belong upstream: say so.

**Visitor data, forms, redirects, tracking** — apply
[`wi-site/references/security-and-deploy.md`](../wi-site/references/security-and-deploy.md)
in full: escaping with `e()` / `e_br()` in pages and emails, visitor URLs
through a http/https check, CSRF on hand-written POST forms and `fetch`,
safe redirects, GTM-only tracking. Any miss is blocking.

**UI** — for public pages and CSS/JS, apply the rules of
[`wi-site/references/style-and-lib.md`](../wi-site/references/style-and-lib.md)
that a diff can show: lib classes before new CSS, no hardcoded colors, no
prefix-based naming, tokens changed in the backend and never in `set-up/`.
Screenshots and external audits are the author's job: if the diff touches
public HTML/CSS and the PR shows no screenshots, say so once.

**Backend** — Resources declare `permissionSchema()` and
`navigationSchema()`; no parallel user-management pages; permission keys
added in `custom/config/permissions.php` (or `access.php`) are used.

**Tests** — a bug fix or a change to visitor data, escaping, CSRF or routes
comes with a test in `tests/` (see the `wi-testing` skill).

**Deploy and config** — changes to `.github/workflows/`, `.htaccess`,
`composer.json`, `package.json`, `custom/config/modules.php` are called out
explicitly, with their effect on the next deploy. New dependencies are
justified.

**Necessity** — flag code that duplicates a framework component, a module,
a lib class or a backend setting the client could change.
