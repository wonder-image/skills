# Wonder Image Agent Skills

Portable Wonder-related skills packaged for the open [`skills`](https://skills.sh) ecosystem.

These skills can be installed with the [`skills` CLI](https://skills.sh/docs/cli) and targeted to Claude Code, Codex, Cursor, or any other supported agent.

## Included skills

- [`wi-app`](skills/wi-app/SKILL.md) — work inside the `wonder-image/app` framework package
- [`wi-site`](skills/wi-site/SKILL.md) — work inside `wonder-image/new-site` or any project scaffolded from it

The two skills are designed as a pair and cross-reference each other.

## Install

**Recommended — install both skills together.** `wi-app` and `wi-site` share a glossary, reference each other for the Model / Resource / Permission contracts, and form a complete picture only when both are present. Specifically:

- `wi-site/SKILL.md` (the **Model, resource, repeater, or table — site postilla** and **Permissions, roles, or user areas — site postilla** sections) points into [`wi-app/references/model-and-resource.md`](skills/wi-app/references/model-and-resource.md) and [`wi-app/references/permissions-and-users.md`](skills/wi-app/references/permissions-and-users.md) for the full framework API.
- `wi-app/SKILL.md` points back to `wi-site` for site-side workflows and to [`wi-site/references/style-and-lib.md`](skills/wi-site/references/style-and-lib.md) for the UI / lib rulebook (it is the authoritative source for default-component work inside `wonder-image/app` too).

Installing only one skill leaves those cross-pointers unresolved on disk — the relative links will 404 locally even though the surface skill works.

```bash
# Recommended — install both together
npx skills add wonder-image/skills --skill '*'
```

Single-skill installs (only do this if you genuinely never touch the other side):

```bash
npx skills add wonder-image/skills --skill wi-app
npx skills add wonder-image/skills --skill wi-site
```

Target a specific agent (defaults to interactive selection):

```bash
npx skills add wonder-image/skills --skill '*' --agent claude-code
npx skills add wonder-image/skills --skill '*' --agent codex
```

Install globally instead of into the current project:

```bash
npx skills add wonder-image/skills --skill '*' --global
```

List the available skills without installing:

```bash
npx skills add wonder-image/skills --list
```

## Install from a local clone

From the root of a local checkout:

```bash
npx skills add . --skill '*'
```

## Structure

```
.
├── skills.sh.json          # optional manifest (grouping/metadata for skills.sh)
└── skills/
    ├── wi-app/
    │   ├── SKILL.md
    │   └── references/
    └── wi-site/
        ├── SKILL.md
        └── references/
```

Each skill directory follows the open agent-skills layout: a `SKILL.md` with YAML frontmatter (`name`, `description`) and optional `references/` and `scripts/` subdirectories.
