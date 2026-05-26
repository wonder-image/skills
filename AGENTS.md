# Wonder Image Skills — Agent Brief

## Project

This repository packages agent skills for the open `skills` ecosystem ([skills.sh](https://skills.sh)). Install with:

```bash
npx skills add wonder-image/skills --skill '*'
```

The skills guide AI coding agents (Claude Code, Codex, Cursor, etc.) when working inside the Wonder Image stack.

## The Wonder Image stack (three repos)

- **`wonder-image/app`** — PHP framework core, distributed as a Composer library. Provides bootstrap, Model / Resource / PageSchema / Repeater architecture, route generation, module discovery, and the `php forge ...` console commands.
  Repo: https://github.com/wonder-image/app.git
- **`wonder-image/lib`** — JS / CSS design system, distributed via npm. Source of truth for tokens, utility classes, and UI primitives. Copied into a Wonder site under `assets/lib/wonder-image/dist/` by `npm install`.
  Repo: https://github.com/wonder-image/lib.git
- **`wonder-image/new-site`** — official site scaffold. Depends on `wonder-image/app` (Composer) and `wonder-image/lib` (npm). Every real Wonder site is bootstrapped from new-site.
  Repo: https://github.com/wonder-image/new-site.git

Clone any of them to inspect real source when writing or reviewing a skill — the reference docs in this repo are written against those checkouts, not from memory.

## Skills in this repo

- **`wi-app`** — activates inside `wonder-image/app` itself. Framework work: bootstrap, registries, route generation, module discovery, console commands, default backend/API CRUD.
- **`wi-site`** — activates inside `wonder-image/new-site` or any project scaffolded from it. Covers project work (pages, models, resources, repeaters, backend-editable content, translations, roles / permissions, component overrides, module integration) **and** UI / styling / design-system work that consumes `wonder-image/lib`.

Each skill lives in `skills/<name>/` with a `SKILL.md` (frontmatter + body) and a `references/` directory of on-demand reference files.

## Repo layout

```
.
├── AGENTS.md                # this file
├── TODO.md                  # all pending work — see rule below
├── README.md                # install / usage docs for end users
├── skills.sh.json           # skills.sh manifest (groupings/metadata)
├── .gitignore
└── skills/
    ├── wi-app/
    │   ├── SKILL.md
    │   └── references/
    └── wi-site/
        ├── SKILL.md
        └── references/
```

## Working rules for agents

### USE, do not MODIFY — documentation framing

**All documentation in this repo is about USING wonder-image libraries and frameworks** (`wonder-image/app`, `wonder-image/lib`), not about modifying their internals.

- References explain extension points, schemas, configuration, and registration — i.e. the public contract of each library.
- They do **not** cover modifying base classes, registries, or design-system primitives.
- When a task would require modifying internals (base class, lib token, framework registry behavior), surface that as a framework-level decision and ask the user before doing it.
- This framing applies to both skills: `wi-app` documents how to extend the framework (Model, Resource, permissions, modules), `wi-site` documents how to consume the framework and `wonder-image/lib` from a site.

### Always put work items in TODO.md

**This is the project-wide convention.** Every time work is identified — open question, residual incongruence, follow-up, idea, deferred refactor, missing example, anything that is not being done in the current turn — write it as an entry in [`TODO.md`](TODO.md). Do not let work items live only in conversation, commit messages, or memory.

Concretely:

- When the user asks for something but explicitly defers part of it → add the deferred part to `TODO.md` before moving on.
- When you discover an inconsistency you are not fixing right now → add it to `TODO.md`.
- When you propose a future improvement → add it to `TODO.md` (under a clearly-labeled "ideas" or "later" section).
- When you complete a `TODO.md` item → check it off in the same edit, do not leave stale entries.
- Keep entries actionable (start with a verb, mention exact file paths and line numbers when applicable).
- Group items by area (`wi-site`, `wi-app`, repo-level, distribution, etc.) so the list stays scannable.

If you are unsure whether something belongs in `TODO.md`, add it. A tracked item that turns out to be irrelevant is cheaper to delete than a lost item is to recover.

### Skill content rules

- Keep `SKILL.md` focused on triggering, non-negotiable rules, and routing to references. Put detailed material in `references/<topic>.md`.
- Frontmatter must include `name` and a `description` that states **TRIGGER when** and **SKIP when** with concrete file paths, commands, and library names.
- Do not duplicate the same rule across SKILL.md and references — pick one canonical location.
- When two skills share a concern, prefer cross-referencing the canonical file over duplicating content.

### Distribution

This repo targets the `skills.sh` flow only (`npx skills add wonder-image/skills`). No `skill.zip` packaging unless explicitly requested. No `package.json` is needed — the CLI discovers skills by scanning `skills/<name>/SKILL.md`.
