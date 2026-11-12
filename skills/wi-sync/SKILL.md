---
name: wi-sync
description: |
  Align a Wonder boilerplate or site with the scaffold wonder-image/new-site:
  AI files, CI, PR template and shared hardening, one target repo at a time.
  TRIGGER when: the user explicitly asks to sync, align or "allineare" a
  repo with new-site (or types /wi-sync).
  SKIP when: any other task, including ordinary work inside a site.
disable-model-invocation: true
---

# Sync a site with the scaffold

`wonder-image/new-site` is the **source**. The boilerplates
(`ecommerce-site`, `immobili-site`, `rsvp-site`) and the sites created from
them are **targets**: they drift because each was copied once and then
edited on its own. This skill brings one target back in line and reports
the **drift** it leaves on purpose.

## Inputs

The target repo (current directory) and a checkout of the source
(`git clone https://github.com/wonder-image/new-site.git` next to it, on
`main`). Never edit the source from here.

## Steps

1. **Inventory.** For every shared item below, compare source and target
   (`diff -u`). Classify each as *identical*, *copy* (target missing or
   behind), *patch* (target has site-specific content around the shared
   part) or *keep* (intentional site difference: say why).
2. **Copy** the *copy* items verbatim.
3. **Patch** the *patch* items: bring in only the shared part and keep the
   site's own lines. Never overwrite a site file wholesale.
4. **Validate** with the target's checks: `composer install --no-scripts` if
   `vendor/` is missing, `php -l` on changed PHP, every `tests/*Test.php`,
   `php .github/scripts/check-lang.php`. A red translation check on a
   multilingual site means missing keys: add them, do not weaken the check.
5. **Deliver** one branch and one PR on the target, body following its
   `.github/pull_request_template.md`. Done when every shared item is
   identical, ported, or listed as *keep* with a reason.

## Shared items

Copied verbatim:

- `AGENTS.md`, `CLAUDE.md`
- `.claude/settings.json`, `.claude/hooks/php-lint.php`
- `.github/workflows/ci.yml`, `.github/scripts/check-lang.php`
- `.github/pull_request_template.md`
- `tests/bootstrap.php`

Patched (shared part only):

- `.github/workflows/deploy.yml` — the `checks` job calling `ci.yml`,
  `needs: checks` on the deploy job, and the FTP `exclude` entries
  (`tests/**`, `.claude/**`, `.agents/**`, `*.md`, `docs/**`).
- `.gitignore` — the `.claude/` block (`/.claude/*`,
  `!/.claude/settings.json`, `!/.claude/hooks/`).
- Contact-form hardening — `App\Support\SafeUrl` (`app/Support/SafeUrl.php`,
  copied), `RequestResource::mailValues()` used for every mail placeholder,
  `custom/view/pages/backend/request/show.php` escaping, and their tests
  `tests/RequestMailEscapeTest.php` / `tests/RequestShowEscapeTest.php`.
  Port the pattern into the site's own `RequestResource`, keeping its extra
  fields; the rules are in
  [`wi-site/references/security-and-deploy.md`](../wi-site/references/security-and-deploy.md).

Never synced: `docs/ai/PRODUCT.md`, `lang/`, `custom/config/` (except when a
shared item above needs a line there), `composer.json`, `README.md`.

## Report

A table of the shared items with their outcome (identical, copied, patched,
keep + reason), the checks run with their result, and the PR link.
