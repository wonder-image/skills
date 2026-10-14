# External skills — routing, installation, precedence

`wi-site` decides *when* an external skill runs inside a Wonder site. The rules live
here and nowhere else: `SKILL.md` routes to this file, `style-and-lib.md` only lists
the closing checks, `product-md.md` only explains why `DESIGN.md` is generated.

## Contents

- [Routing table](#routing-table)
- [Detection and installation](#detection-and-installation)
- [Precedence](#precedence)
- [Passing context to external skills](#passing-context-to-external-skills)
- [Fallback without DESIGN.md](#fallback-without-designmd)
- [Reports](#reports)
- [Site readiness check](#site-readiness-check)

## Routing table

| Need | Skill | Source | Scope in a Wonder site |
|---|---|---|---|
| New public page, hero, landing, section with visual impact | `design-taste-frontend` | `Leonxlnx/taste-skill` | Public frontend only. Never backend pages, dashboards, admin forms. |
| "Redo / improve / modernise" an existing public page | `redesign-existing-projects` | `Leonxlnx/taste-skill` | Audit → diagnosis → fix inside the existing stack (PHP views + `wonder-image/lib`). No rewrites, no new stack. |
| The user provides a mockup or screenshot to reproduce | `image-to-code` | `Leonxlnx/taste-skill` | Extract structure, hierarchy, spacing from the image the user gave; map onto `wonder-image/lib` components. No image generation: skip every step of that skill that generates images. |
| Review of accessibility, UX, perceived performance, UI copy | `web-design-guidelines` | `vercel-labs/agent-skills` | On the frontend files touched by the task; `file:line` findings in the report. |
| See the page: screenshots at 375 / 768 / 1280, console | `playwright-cli` | `npm i -g @playwright/cli@latest` + `playwright-cli install --skills` | Every task that touches public HTML / CSS, before the Required Final Report. |

Activation, in the order `wi-site` checks it:

- **Backend page, dashboard, admin form, model, resource, translation, setup task:** no external
  skill. Finish with the Required Final Report of `style-and-lib.md` only. Taste's house style is
  written for public pages; applying it to the backend fights the framework's own components.
- **New public page or section:** `design-taste-frontend`.
- **Existing public page the user wants redone or improved:** `redesign-existing-projects`.
- **Mockup or screenshot supplied by the user:** `image-to-code`, then build with lib components.
- **Any task that touched public HTML / CSS, before closing:** `playwright-cli` screenshots and
  console, then `web-design-guidelines` on the touched files.

A task can chain several rows (new page → taste → playwright-cli → web-design-guidelines). Tell the
user which skill you are using and why, in one line, the first time it enters a task.

## Detection and installation

Before using a skill, look for its `SKILL.md`. Read the one you find; do not assume its flags from
memory, they change between versions.

- `~/.agents/skills/<name>/SKILL.md` (where `npx skills add --global` installs)
- `~/.claude/skills/<name>/SKILL.md` (symlink created by the same install)
- taste installed as a plugin: `~/.claude/plugins/cache/taste-skill/*/skills/*/SKILL.md`. Folder
  names are `taste-skill`, `redesign-skill`, `image-to-code-skill`; the `name` in each frontmatter
  is the one in the routing table.
- `playwright-cli`: `command -v playwright-cli` plus the skill written by
  `playwright-cli install --skills` (run it from the home directory so the skill folder lands
  outside the site repo). Without the skill, `playwright-cli --help` lists the commands.

If the skill is missing, tell the user what you are about to install and run only the matching
line:

```bash
npx skills add Leonxlnx/taste-skill --skill design-taste-frontend --global
npx skills add Leonxlnx/taste-skill --skill redesign-existing-projects --global
npx skills add Leonxlnx/taste-skill --skill image-to-code --global
npx skills add vercel-labs/agent-skills --skill web-design-guidelines --global
npm i -g @playwright/cli@latest && playwright-cli install --skills
```

- Verify the `--skill` names once per machine with `npx skills add Leonxlnx/taste-skill --list`:
  the name is the frontmatter `name`, not the folder.
- Install the skill the task needs, not the whole package.
- `--global`, always. The installation belongs to the developer, not to the site: no `.agents/`,
  no `skills-lock.json`, no `.claude/skills/` entry inside the site repo. If the site already has
  such entries from an older setup, leave them and report them.
- Taste's scripts need Node 22+ and an installed Chrome or Edge (`lint.mjs` runs on Node 18+,
  `inspect.mjs` needs the browser). Check with `node --version` at first use; if the requirement is
  missing, say so and fall back to `playwright-cli` for screenshots instead of skipping the checks.

## Precedence

`docs/ai/PRODUCT.md`, the tokens of `root.css` / `color.css` as projected in `docs/ai/DESIGN.md`,
and `style-and-lib.md` win over any proposal from an external skill. External skills decide
composition, copy, rhythm and motion. A new palette, typeface or CSS value is mapped onto an
existing token or utility class, or discarded. Three concrete cases:

1. **Taste proposes a palette.** Run `draw.mjs` with `--keep palette,type`. The dealt direction
   then carries no colours or typefaces: map every role it names onto the palette roles of
   `DESIGN.md` (`primary`, `secondary`, `dark`, `light` and so on) and the matching utility classes
   (`.bg-{r}`, `.tx-{r}`, `.btn-{r}`). Never add a colour to `color.css` for a direction.
2. **`web-design-guidelines` proposes a CSS fix.** Apply it with lib utility classes or the
   variables listed in `DESIGN.md`, never with hardcoded values. If no token covers it, leave the
   finding open in the `wdg-review` report with the reason, instead of inventing a value.
3. **`image-to-code` extracts a typeface or a size from the mockup.** Use the closest typography
   group from `DESIGN.md` (`title-big`, `title`, `subtitle`, `text`, `text-small`) and record the
   difference in the final report. Changing a font family is a backend decision (CSS → Font), not a
   side effect of a mockup.

The same rule covers spacing (`--spacer` multiples, lib spacing utilities), radii and borders
(button / badge tokens): a value that is not a token is a value to question.

## Passing context to external skills

Verified in taste's scripts: `--keep palette,type` reads no file. It only removes palette and
typography from the deal and leaves the mapping to the agent. So `wi-site` reads
`docs/ai/DESIGN.md` and `docs/ai/PRODUCT.md` first and transcribes what matters into the flags:

```bash
node <skill>/scripts/draw.mjs --sector <closest sector for site_type and target users> \
  --brief "<one line about the subject, in the voice of PRODUCT.md>" \
  --sections <roles in the order of the page's argument> \
  --tone <from PRODUCT.md voice / tone, only if it is clearly one of the script's tones> \
  --have <assets that really exist, from PRODUCT.md asset paths> \
  --keep palette,type
```

Then map by hand: colour roles and font groups of `DESIGN.md` onto the dealt sections. The same
holds for `redesign-existing-projects` (its audit with `lint.mjs` / `inspect.mjs` runs on the
existing page; the fixes stay inside the token set) and for `image-to-code` (the mockup gives
structure, `DESIGN.md` gives colours and type). `DESIGN.md` is context for the agent, never an
input of the scripts.

## Fallback without DESIGN.md

`docs/ai/DESIGN.md` is written by `wonder-image/app` every time it regenerates `root.css`, only in
development (`APP_DEBUG=true` or a local host). If the file is missing:

1. Run `php forge update --local` once. On a current framework this writes it.
2. Still missing: the site's framework predates the generator. Read
   `assets/{ASSETS_VERSION}/css/set-up/root.css` and `color.css` directly (the variables and
   classes are the same ones `DESIGN.md` lists) and tell the user that
   `composer update wonder-image/app` will bring the file.

Never write `DESIGN.md` by hand and never edit it: the next save from the backend overwrites it,
and a hand-written copy would be exactly the parallel token source the generated file exists to
prevent.

## Reports

Every external skill writes its output as a file in `docs/ai/reports/`, named
`YYYY-MM-DD-<task>-<type>.md`:

- `<task>`: a short slug of the task (`chi-siamo`, `home-hero`).
- `<type>`: `taste-critique` (taste's direction and critique), `redesign-audit`
  (`redesign-existing-projects`), `wdg-review` (`web-design-guidelines` findings, `file:line`),
  `inspect` (the `inspect.mjs` result), `final-report` (the Required Final Report of
  `style-and-lib.md`, saved as a file in addition to the chat summary).

Screenshots from `playwright-cli` go in the same folder as `YYYY-MM-DD-<task>-<width>.png`, and the
final report lists them. The folder is gitignored (`/docs/ai/reports/*`): nothing in it is
committed. Taste's working files stay where taste puts them, `.taste-skill/` at the site root,
gitignored too.

Closing check for any task that touched public HTML / CSS (`<url>` is the local URL of the page,
from `php forge start` or given by the user):

```bash
playwright-cli open <url>
playwright-cli resize 375 812  && playwright-cli screenshot --filename=docs/ai/reports/YYYY-MM-DD-<task>-375.png
playwright-cli resize 768 1024 && playwright-cli screenshot --filename=docs/ai/reports/YYYY-MM-DD-<task>-768.png
playwright-cli resize 1280 800 && playwright-cli screenshot --filename=docs/ai/reports/YYYY-MM-DD-<task>-1280.png
playwright-cli console
playwright-cli close
```

Look at the three screenshots before writing the report; a console error or a broken layout at
any width blocks the Pre-Submit Checklist of `style-and-lib.md`.

## Site readiness check

Run once per session, the first time `wi-site` touches a site. Fix silently what can be fixed;
list what was touched in the Required Final Report. Never commit on the user's behalf: `git mv`
stages the move, the user reviews it.

1. **`PRODUCT.md` location.**
   - `PRODUCT.md` at the root, `docs/ai/PRODUCT.md` absent, root file tracked
     (`git ls-files --error-unmatch PRODUCT.md` exits 0): `mkdir -p docs/ai && git mv PRODUCT.md docs/ai/PRODUCT.md`.
   - Same, but the root file is not tracked (exit code 1): `mkdir -p docs/ai && mv PRODUCT.md docs/ai/PRODUCT.md`,
     then tell the user the file is new to git.
   - Both files exist and `docs/ai/PRODUCT.md` is still the untouched scaffold template (every
     section holds only `>` hint lines or HTML comments) while the root file has content: the root
     file is the real one, `git mv -f PRODUCT.md docs/ai/PRODUCT.md` over the template and report
     it. Both have content: do not touch either; report it and read `docs/ai/PRODUCT.md`.
   - Neither exists: the interview in `product-md.md` creates `docs/ai/PRODUCT.md`.
2. **`.gitignore`.** If these four lines are missing, append them at the end of the block that
   starts with `# Generated/local site configuration`, or at the end of the file if the block is
   missing:
   ```
   /docs/ai/DESIGN.md
   /docs/ai/reports/*
   !/docs/ai/reports/.gitkeep
   /.taste-skill/
   ```
3. **`.github/workflows/deploy.yml`.** If the file exists, uses `SamKirkland/FTP-Deploy-Action`
   and its `exclude: |` list has no `docs/**`, add `docs/**` right after the `*.md` line with the
   same indentation. If the file is missing or does not use that action, report it and do not
   touch it: `docs/ai/PRODUCT.md` is committed and must not reach a public webroot.
4. **`docs/ai/reports/`.** If missing, create it with an empty `.gitkeep`.
