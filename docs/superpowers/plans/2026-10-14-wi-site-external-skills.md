# `wonder-image/skills`: routing delle skill esterne da `wi-site` — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `wi-site` legge `docs/ai/PRODUCT.md` e `docs/ai/DESIGN.md`, richiama da sola la skill esterna giusta (taste, redesign, image-to-code, web-design-guidelines, playwright-cli), la installa a richiesta con `--global`, fa vincere i token del sito su ogni proposta esterna e scrive i report in `docs/ai/reports/`.

**Architecture:** Un nuovo riferimento `skills/wi-site/references/external-skills.md` è l'unico posto con tabella di routing, rilevamento/installazione, precedenza, passaggio del contesto, fallback senza `DESIGN.md`, naming dei report e site readiness check. `SKILL.md` e gli altri riferimenti rimandano lì con una riga ciascuno e perdono la politica "no DESIGN.md" e ogni percorso `PRODUCT.md` in root. `wi-app` cambia un solo segnale di riconoscimento. Nessuno script, nessun file nuovo oltre al riferimento.

**Tech Stack:** Markdown (SKILL.md con frontmatter per skills.sh), `grep` per le verifiche, git.

**Spec:** `docs/superpowers/specs/2026-10-14-external-skills-routing-design.md`, sezioni 2, 3, 6, 8, 9. Terzo dei tre piani (sezione 7): si esegue dopo `2026-10-14-app-design-md-generator.md` e `2026-10-14-new-site-docs-ai.md`, ma il testo delle skill deve funzionare anche sui siti con il framework vecchio (fallback senza `DESIGN.md`).

## Global Constraints

- **Repo e cwd:** `/Users/andreamarinoni/Developer/packages/skills` (questo repo, branch `main`, pulito: i piani sono già committati in `649c68a`). Branch `wi-site/external-skills` da `main`, una PR su `main`.
- **Lingua:** `external-skills.md` è in **inglese**, come `SKILL.md`, `style-and-lib.md`, `examples.md`, `header-navigation.md`. `product-md.md` resta in italiano com'è. Le voci di `TODO.md` in inglese come le altre.
- **Una sola fonte:** le regole sulle skill esterne stanno solo in `external-skills.md`. `SKILL.md`, `style-and-lib.md`, `product-md.md`, `examples.md`, `header-navigation.md` rimandano con una riga, non ripetono tabelle o comandi.
- **Percorsi:** `PRODUCT.md` è `docs/ai/PRODUCT.md` ovunque; `PRODUCT.md` in root si cita solo come fallback nei siti non migrati. `DESIGN.md` è `docs/ai/DESIGN.md`, "generato dal framework: leggilo, non modificarlo". Nessuna skill cita più `impeccable`.
- **Comandi di installazione**, verbatim dalla spec 6.1 (con `--global`):
  ```bash
  npx skills add Leonxlnx/taste-skill --skill design-taste-frontend --global
  npx skills add Leonxlnx/taste-skill --skill redesign-existing-projects --global
  npx skills add Leonxlnx/taste-skill --skill image-to-code --global
  npx skills add vercel-labs/agent-skills --skill web-design-guidelines --global
  npm i -g @playwright/cli@latest && playwright-cli install --skills
  ```
- **Report:** `docs/ai/reports/YYYY-MM-DD-<task>-<tipo>.md`, `<tipo>` ∈ {`taste-critique`, `redesign-audit`, `wdg-review`, `final-report`, `inspect`}.
- **Anchor GFM:** il titolo di `examples.md` §6 cambia, quindi cambia lo slug da `#6-productmd--brand-and-project-context` a `#6-docsaiproductmd--brand-and-project-context`. Cinque link lo usano (`README.md:35`, `examples.md:10`, `SKILL.md:67`, `product-md.md:150`, `product-md.md:199`): vanno aggiornati tutti nel Task 5. Lo slug di `product-md.md` "The DESIGN.md policy" (`#the-designmd-policy`) è usato solo dal TOC a riga 9.
- **`TODO.md`** è locale e gitignorato di proposito: si aggiorna (Task 6), non si mette mai in staging, nemmeno con `git add -f`.
- **Commit:** ogni messaggio termina con `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`. `git add` sempre con i path espliciti.
- **Nessuna installazione reale** di skill esterne in questo piano: si scrive la documentazione. L'unico comando "live" è `npx skills add Leonxlnx/taste-skill --list` in Task 1 per confermare i nomi, e si salta se la rete manca.

## Review Focus

1. **Sito con `PRODUCT.md` in root non tracciato da git** (file creato ma mai committato): `git mv` fallisce. Il readiness check deve distinguere tracciato (`git ls-files --error-unmatch`) da non tracciato (`mkdir -p docs/ai && mv`) e dal caso in cui esistono entrambi (non toccare, riferire). Scritto per esteso in Task 1, § Site readiness check.
2. **Sito con framework vecchio**: `docs/ai/DESIGN.md` manca anche dopo `php forge update --local`. Il fallback legge `root.css`/`color.css` e avvisa che `composer update wonder-image/app` porta il file; mai scrivere `DESIGN.md` a mano. Task 1, § Fallback.
3. **Task sul backend** ("aggiungi un campo al form del backend"): nessuna skill esterna, nemmeno playwright-cli; solo il Required Final Report. La tabella di routing e la riga di SKILL.md lo dicono in modo esplicito (Task 1 e Task 2); il test manuale è in Task 6.
4. **`deploy.yml` assente o senza `SamKirkland/FTP-Deploy-Action`** (sito deployato altrimenti): il readiness check riferisce e non tocca il file. Task 1, § Site readiness check, punto 3.
5. **Link e anchor rotti** dopo il cambio di titolo in `examples.md` §6 e della sezione in `product-md.md`: `grep` finale in Task 5 deve trovare zero occorrenze dei vecchi slug e dei vecchi testi ("at the site root", "at site root", "a root del sito", "Do not create a `DESIGN.md`", "impeccable").

---

### Task 0: aprire il branch

- [ ] **Step 1: Stato e branch**

```bash
cd /Users/andreamarinoni/Developer/packages/skills && git checkout main && git status --short && git checkout -b wi-site/external-skills
```

Expected: `git status --short` vuoto (`TODO.md` non compare perché gitignorato), branch creato da `main`.

---

### Task 1: nuovo riferimento `external-skills.md`

**Files:**
- Create: `skills/wi-site/references/external-skills.md`

**Interfaces:**
- Produces: le sezioni (anchor GFM) a cui gli altri file rimandano: `#routing-table`, `#detection-and-installation`, `#precedence`, `#passing-context-to-external-skills`, `#fallback-without-designmd`, `#reports`, `#site-readiness-check`.

- [ ] **Step 1: Confermare i nomi `--skill` di taste (facoltativo, richiede rete)**

```bash
npx skills add Leonxlnx/taste-skill --list 2>&1 | tail -20
```

Expected: tra le skill elencate compaiono `design-taste-frontend`, `redesign-existing-projects`, `image-to-code`. Se il comando non va (rete, versione CLI) i nomi restano quelli del frontmatter già verificati nei file `~/.claude/plugins/cache/taste-skill/taste-skill/2.0.0/skills/*/SKILL.md`; annotare in PR che la verifica è saltata.

- [ ] **Step 2: Scrivere il file**

Creare `skills/wi-site/references/external-skills.md` con esattamente questo contenuto:

````markdown
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
   - Both files exist: do not touch either; report it and read `docs/ai/PRODUCT.md`.
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
````

- [ ] **Step 3: Verifica**

```bash
cd /Users/andreamarinoni/Developer/packages/skills
grep -c '^## ' skills/wi-site/references/external-skills.md
grep -n 'impeccable\|awesome-design-md' skills/wi-site/references/external-skills.md; echo "nessuna skill scartata citata (atteso 1): $?"
grep -n -- '--global' skills/wi-site/references/external-skills.md | wc -l
```

Expected: `8` sezioni `##` (Contents + 7); grep delle skill scartate vuoto con exit 1; almeno 5 righe con `--global`.

- [ ] **Step 4: Commit**

```bash
git add skills/wi-site/references/external-skills.md
git commit -m "$(cat <<'MSG'
wi-site: riferimento external-skills.md (routing, installazione, precedenza, report)

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

---

### Task 2: `skills/wi-site/SKILL.md`

**Files:**
- Modify: `skills/wi-site/SKILL.md:7` (frontmatter), `:63-67` (Start Sequence passo 0), `:84` (Brand), `:86-92` (Page or component), `:94-100` (UI / styling), nuova sezione dopo la riga 100

**Interfaces:**
- Consumes: gli anchor di `references/external-skills.md` (Task 1).

- [ ] **Step 1: Frontmatter, riga 7**

Sostituire:

```
  `assets/{ASSETS_VERSION}/`, `PRODUCT.md`, `php forge credentials`,
```

con:

```
  `assets/{ASSETS_VERSION}/`, `docs/ai/` (`PRODUCT.md`, `DESIGN.md`), `php forge credentials`,
```

- [ ] **Step 2: Start Sequence, passo 0 e nuovo passo 0b**

Sostituire il blocco che inizia con `0. **Read \`PRODUCT.md\` at the site root first.**` e finisce con la riga `   The interview is sequential and adaptive: ... §6](references/examples.md#6-productmd--brand-and-project-context).` (righe 63-67) con:

```markdown
0. **Read `docs/ai/PRODUCT.md` first.** It defines register (brand vs product), `site_type`, target users, brand personality, voice/tone, design principles. Everything you do next must be coherent with what it says — placement choices, copy, suggestions of modules, UI direction. If the file is missing but `PRODUCT.md` exists at the site root, the site is not migrated yet: read the root file, then run the **Site readiness check** in [`references/external-skills.md`](references/external-skills.md#site-readiness-check) (it moves the file with `git mv`, adds the `.gitignore` and `deploy.yml` lines, creates `docs/ai/reports/`). Run that check once per session on every site, migrated or not.

   **If `docs/ai/PRODUCT.md` is missing or its required sections are empty, do NOT proceed with the user's task yet.** Run the interview procedure in [`references/product-md.md` § Compilation procedure](references/product-md.md#compilation-procedure-interview-driven): ask 2–4 focused questions at a time with `AskUserQuestion`, save answers into `docs/ai/PRODUCT.md` as they come, and continue interviewing until you have **at least 80% project clarity** — operationalized as: all 6 required sections filled (`Register`, `Site type`, `Target users`, `Brand personality`, `Voice / tone`, `Design principles`) plus at least one of (`Anti-references`, `Asset paths`). Only then proceed with the original task. Reason: the alternative is generic output that the user will have to fix later — the 10–15 minutes spent interviewing pay back on every subsequent decision in the session and in future sessions.

   The interview is sequential and adaptive: if the user already mentioned something in passing (e.g. "abbiamo un brand di moda"), pre-fill the relevant section and ask for confirmation rather than re-asking from scratch. Full schema in [`references/product-md.md`](references/product-md.md); copy-paste template in [`references/examples.md` §6](references/examples.md#6-docsaiproductmd--brand-and-project-context).

   **Then read `docs/ai/DESIGN.md` if the task touches UI** (pages, components, styling, tokens). The framework generates it from the CSS tokens every time it writes `root.css`: read it, never edit it. If it is missing, follow [`references/external-skills.md` § Fallback without DESIGN.md](references/external-skills.md#fallback-without-designmd).
```

- [ ] **Step 3: Task Routing, "Brand, copywriting, voice, site type" (riga 84)**

Sostituire la riga:

```markdown
Read [`references/product-md.md`](references/product-md.md). `PRODUCT.md` at site root is the source of truth for register, target users, brand personality, voice/tone, design principles, and `site_type` (`landing` / `corporate` / `blog` / `ecom` / `rsvp`). Copy lives in `lang/{locale}/*.json` and must respect the voice declared in `PRODUCT.md`. Do **not** create a `DESIGN.md` — Wonder's `color.css` + `root.css` + [`style-and-lib.md`](references/style-and-lib.md) are authoritative for visual tokens.
```

con:

```markdown
Read [`references/product-md.md`](references/product-md.md). `docs/ai/PRODUCT.md` is the source of truth for register, target users, brand personality, voice/tone, design principles, and `site_type` (`landing` / `corporate` / `blog` / `ecom` / `rsvp`). Copy lives in `lang/{locale}/*.json` and must respect the voice declared in `docs/ai/PRODUCT.md`. `docs/ai/DESIGN.md` is generated by the framework from `color.css` + `root.css`: read it, do not edit it; the rules in [`style-and-lib.md`](references/style-and-lib.md) stay authoritative for visual tokens.
```

- [ ] **Step 4: "Page or component" (righe 86-92)**

Dopo l'ultimo bullet `- Inspect existing page components before adding new markup.` aggiungere:

```markdown
- Public page? Route through [`references/external-skills.md`](references/external-skills.md#routing-table): new public page or section → `design-taste-frontend`; existing public page to redo → `redesign-existing-projects`; mockup or screenshot supplied → `image-to-code`. Backend pages: no external skill.
```

- [ ] **Step 5: "UI / styling / design system" (righe 94-100)**

Prima dell'ultimo bullet (`- End every UI task with the **Required Final Report** ...`) aggiungere:

```markdown
- Before closing any task that touched public HTML / CSS: `playwright-cli` screenshots at 375 / 768 / 1280 and console, then `web-design-guidelines` on the touched files, as described in [`references/external-skills.md`](references/external-skills.md#reports). Backend-only tasks skip both.
```

- [ ] **Step 6: Nuova voce di routing "External skills"**

Dopo la sezione "UI / styling / design system" (dopo il bullet `- End every UI task ...` e prima di `### Model, resource, repeater, or table — site postilla`) inserire:

```markdown
### External skills
Read [`references/external-skills.md`](references/external-skills.md). It is the only place with the routing table (taste, redesign, image-to-code, web-design-guidelines, playwright-cli), detection and on-demand installation with `--global`, the precedence of `docs/ai/PRODUCT.md` + `docs/ai/DESIGN.md` + `style-and-lib.md` over external proposals, the report naming in `docs/ai/reports/`, and the site readiness check. Do not repeat those rules here.

```

- [ ] **Step 7: Verifica**

```bash
grep -n 'at the site root first\|at site root\|Do \*\*not\*\* create a `DESIGN.md`\|6-productmd--' skills/wi-site/SKILL.md; echo "vecchi testi (atteso 1): $?"
grep -c 'external-skills.md' skills/wi-site/SKILL.md
grep -n 'Then read `docs/ai/DESIGN.md`\|^### External skills' skills/wi-site/SKILL.md
wc -l skills/wi-site/SKILL.md
```

Expected: nessun vecchio testo (exit 1); almeno 5 rimandi a `external-skills.md`; le due righe nuove trovate; file sotto le 200 righe.

- [ ] **Step 8: Commit**

```bash
git add skills/wi-site/SKILL.md
git commit -m "$(cat <<'MSG'
wi-site: SKILL.md legge docs/ai/PRODUCT.md e DESIGN.md e instrada alle skill esterne

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

---

### Task 3: `references/style-and-lib.md`

**Files:**
- Modify: `skills/wi-site/references/style-and-lib.md:102` (regola DESIGN.md), `:134-143` (Required Final Report), `:154-163` (Pre-Submit Checklist)

- [ ] **Step 1: Riga 102**

Sostituire il paragrafo:

```markdown
**Do not create a `DESIGN.md` at site root.** Visual tokens live in `assets/{ASSETS_VERSION}/css/set-up/color.css` + `root.css` and the rules in this file are the rulebook. A second `DESIGN.md` would create a parallel token source that drifts from `color.css` / `root.css` over time; this is documented self-defense against duplication. Strategic context belongs in [`PRODUCT.md`](product-md.md).
```

con:

```markdown
**`docs/ai/DESIGN.md` is the readable projection of the tokens.** `wonder-image/app` generates it from `color.css` + `root.css` every time they are regenerated in development, so it cannot drift from them. Values come from there or from `root.css` / `color.css`, never from an external skill: a palette, typeface or CSS value proposed by `design-taste-frontend`, `web-design-guidelines` or `image-to-code` is mapped onto an existing token or discarded (precedence in [`external-skills.md`](external-skills.md#precedence)). Do not edit `DESIGN.md` by hand: the next save overwrites it. Strategic context belongs in [`docs/ai/PRODUCT.md`](product-md.md).
```

- [ ] **Step 2: Required Final Report (righe 134-143)**

Sostituire il blocco:

```markdown
## Required Final Report

After completing UI work, report explicitly:

- **Files modified** with paths.
- **Lib classes used** (list — both utilities and `.wi-*` components).
- **Tokens / variables used**.
- **Tokens modified or added** (if any).
- **Commands executed**.
- **Residual risks** (visual regression on other pages, dark-mode untested, responsive behavior not verified, etc.).
```

con:

```markdown
## Required Final Report

After completing UI work, save the report as `docs/ai/reports/YYYY-MM-DD-<task>-final-report.md` (the folder is gitignored; naming in [`external-skills.md` § Reports](external-skills.md#reports)) and give the same content, shortened, in chat. Report explicitly:

- **Files modified** with paths.
- **Lib classes used** (list — both utilities and `.wi-*` components).
- **Tokens / variables used**.
- **Tokens modified or added** (if any).
- **Commands executed**.
- **Screenshots** (`playwright-cli`, paths in `docs/ai/reports/`) and the **findings file** (`...-wdg-review.md`), with each finding fixed or justified.
- **External skills used** and what their proposals were mapped onto (or discarded), per the precedence rules.
- **Site readiness check**: which items were touched (`PRODUCT.md` move, `.gitignore`, `deploy.yml`, `docs/ai/reports/`), or "nothing to do".
- **Residual risks** (visual regression on other pages, dark-mode untested, responsive behavior not verified, etc.).
```

- [ ] **Step 3: Pre-Submit Checklist (righe 154-163)**

Prima dell'ultima voce `- [ ] Reported files, classes, tokens, commands as listed in **Required Final Report**` aggiungere:

```markdown
- [ ] `playwright-cli` screenshots at 375 / 768 / 1280 saved in `docs/ai/reports/` and browser console clean (public HTML / CSS tasks only)
- [ ] `web-design-guidelines` review run on the touched files; every finding fixed or justified in the `wdg-review` report (public HTML / CSS tasks only)
```

- [ ] **Step 4: Verifica**

```bash
grep -n 'Do not create a `DESIGN.md`\|at site root\|\[`PRODUCT.md`\](product-md.md)' skills/wi-site/references/style-and-lib.md; echo "vecchi testi (atteso 1): $?"
grep -c '^- \[ \]' skills/wi-site/references/style-and-lib.md
grep -n 'final-report.md' skills/wi-site/references/style-and-lib.md
```

Expected: exit 1; `13` voci di checklist (11 + 2); una riga con `final-report.md`.

- [ ] **Step 5: Commit**

```bash
git add skills/wi-site/references/style-and-lib.md
git commit -m "$(cat <<'MSG'
wi-site: style-and-lib.md usa DESIGN.md generato, report in docs/ai/reports, check playwright e WDG

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

---

### Task 4: `references/product-md.md`

**Files:**
- Modify: `skills/wi-site/references/product-md.md:9` (TOC), `:22-37` (Where it lives), `:117-125` (DESIGN.md policy), `:129`, `:150`, `:172`, `:187`, `:199`

- [ ] **Step 1: TOC, riga 9**

Sostituire `- [The DESIGN.md policy](#the-designmd-policy)` con `- [DESIGN.md: generato dal framework](#designmd-generato-dal-framework)`.

- [ ] **Step 2: "Where it lives" (righe 22-37)**

Sostituire dal paragrafo `**A root del repo del sito.** ...` fino a `... \`wi-site\` la cerca alla root per evitare configurazione extra.` compreso, con:

````markdown
**In `docs/ai/PRODUCT.md`.** Versionato con git, insieme agli altri file che le skill leggono o scrivono in un sito:

```
my-wonder-site/
├── composer.json
├── package.json
├── docs/
│   └── ai/
│       ├── PRODUCT.md      ← qui (committato)
│       ├── DESIGN.md       ← generato dal framework, gitignorato
│       └── reports/        ← output delle skill, gitignorato
├── custom/
├── app/
├── lang/
└── assets/
```

Una sola istanza per sito. I siti nati prima di questa convenzione hanno `PRODUCT.md` a root: `wi-site` lo legge comunque e lo sposta con `git mv` nel [site readiness check](external-skills.md#site-readiness-check), la prima volta che tocca il sito. Lo scaffold `new-site` lo crea già in `docs/ai/` con le sezioni vuote.
````

- [ ] **Step 3: Sezione DESIGN.md (righe 117-125)**

Sostituire dal titolo `## The DESIGN.md policy` fino a `... impossibile mantenere coerente nel tempo.` compreso, con:

```markdown
## DESIGN.md: generato dal framework

`docs/ai/DESIGN.md` esiste, ma non lo scrive nessuno a mano: `wonder-image/app` lo genera dai token di `assets/{ASSETS_VERSION}/css/set-up/color.css` e `root.css` ogni volta che li rigenera in sviluppo (palette, tipografia, spaziatura, bottoni, classi utility). È gitignorato come `root.css` e `color.css`, perché deriva dal DB.

`PRODUCT.md` dice *chi* e *perché*; `DESIGN.md` dice *con quali colori e font*. Le proposte visive delle skill esterne si mappano su quei token o si scartano: la regola di precedenza e il fallback per i siti con il framework non aggiornato stanno in [`external-skills.md`](external-skills.md#precedence), le regole d'uso dei token in [`style-and-lib.md`](style-and-lib.md).
```

- [ ] **Step 4: Riga 129 ("How wi-site uses it")**

Sostituire:

```markdown
Il primo step di `wi-site` su qualsiasi task di un sito Wonder è: leggere `PRODUCT.md` a root. Tutto quello che segue — placement, copy, scelte di stile, suggerimenti di moduli — deve essere coerente con quanto dichiarato lì.
```

con:

```markdown
Il primo step di `wi-site` su qualsiasi task di un sito Wonder è: leggere `docs/ai/PRODUCT.md` (o `PRODUCT.md` a root nei siti non ancora migrati, che il readiness check sposta). Tutto quello che segue — placement, copy, scelte di stile, suggerimenti di moduli — deve essere coerente con quanto dichiarato lì.
```

- [ ] **Step 5: Righe 150, 172, 187, 199**

Riga 150: sostituire `[`examples.md` §6](examples.md#6-productmd--brand-and-project-context)` con `[`examples.md` §6](examples.md#6-docsaiproductmd--brand-and-project-context)`.

Riga 172: sostituire `l'assistant aggiorna \`PRODUCT.md\` a root del sito con le risposte` con `l'assistant aggiorna \`docs/ai/PRODUCT.md\` con le risposte`.

Riga 187: sostituire `- Crea un \`PRODUCT.md\` placeholder con le 6 sezioni richieste` con `- Crea \`docs/ai/PRODUCT.md\` placeholder con le 6 sezioni richieste`.

Riga 199: sostituire `Copia il template da [\`examples.md\` §6](examples.md#6-productmd--brand-and-project-context) e compilalo.` con `Copia il template da [\`examples.md\` §6](examples.md#6-docsaiproductmd--brand-and-project-context) in \`docs/ai/PRODUCT.md\` e compilalo.`.

- [ ] **Step 6: Verifica**

```bash
grep -n 'a root del sito\|alla root\|the-designmd-policy\|6-productmd--\|Non creare' skills/wi-site/references/product-md.md
grep -c 'docs/ai/PRODUCT.md' skills/wi-site/references/product-md.md
```

Expected: la prima `grep` trova solo le righe che citano la root come fallback dei siti non migrati (sezioni "Where it lives" e "How wi-site uses it"); nessun vecchio anchor, nessun "Non creare"; almeno 5 occorrenze di `docs/ai/PRODUCT.md`.

- [ ] **Step 7: Commit**

```bash
git add skills/wi-site/references/product-md.md
git commit -m "$(cat <<'MSG'
wi-site: product-md.md vive in docs/ai, DESIGN.md generato dal framework

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

---

### Task 5: `examples.md`, `header-navigation.md`, `wi-app/SKILL.md`, `README.md`

**Files:**
- Modify: `skills/wi-site/references/examples.md:10` (TOC), `:359-365` (§6 titolo e intro), `:426-431` (chiusura §6)
- Modify: `skills/wi-site/references/header-navigation.md:193`
- Modify: `skills/wi-app/SKILL.md:44`
- Modify: `README.md:35` e blocco "Install globally" (righe 44-48)
- Modify: `skills/wi-site/SKILL.md:67` (anchor §6, già aggiornato in Task 2 Step 2: solo verifica)

- [ ] **Step 1: `examples.md` §6**

Riga 10 (TOC): sostituire `- [6. \`PRODUCT.md\` — brand and project context](#6-productmd--brand-and-project-context)` con `- [6. \`docs/ai/PRODUCT.md\` — brand and project context](#6-docsaiproductmd--brand-and-project-context)`.

Riga 359: sostituire `## 6. \`PRODUCT.md\` — brand and project context` con `## 6. \`docs/ai/PRODUCT.md\` — brand and project context`.

Riga 361: sostituire `A single markdown file at the **root of the site repo** (same level as \`composer.json\`). Captures` con `A single markdown file at **\`docs/ai/PRODUCT.md\`** (the \`new-site\` scaffold ships it with empty sections; older sites have it at the root until the readiness check moves it). Captures`.

Riga 365: sostituire `Copy this skeleton into \`PRODUCT.md\` and fill` con `Copy this skeleton into \`docs/ai/PRODUCT.md\` and fill`.

Righe 426-431, sostituire:

```markdown
After creating `PRODUCT.md`:

- No further config needed — `wi-site` looks for it at the repo root.
```

con:

```markdown
After creating `docs/ai/PRODUCT.md`:

- No further config needed — `wi-site` looks for it at `docs/ai/PRODUCT.md` (and falls back to `PRODUCT.md` at the root on sites not yet migrated).
```

e la riga:

```markdown
- Do **not** create a `DESIGN.md` — Wonder's `color.css` + `root.css` + [`style-and-lib.md`](style-and-lib.md) are the authoritative visual rulebook.
```

con:

```markdown
- Read `docs/ai/DESIGN.md` for the visual tokens, never create or edit it: the framework generates it from `color.css` + `root.css` (rules in [`style-and-lib.md`](style-and-lib.md), precedence over external skills in [`external-skills.md`](external-skills.md#precedence)).
```

- [ ] **Step 2: `header-navigation.md:193`**

Sostituire `9. Check the browser console and test at phone, tablet, and desktop widths.` con `9. Run the closing check of [\`external-skills.md\` § Reports](external-skills.md#reports): \`playwright-cli\` screenshots at 375 / 768 / 1280 and a clean console, then \`web-design-guidelines\` on the touched files.`.

- [ ] **Step 3: `wi-app/SKILL.md:44`**

Sostituire:

```markdown
- a `PRODUCT.md` exists at the repo root declaring a Wonder site (`site_type: landing` / `corporate` / `blog` / `ecom` / `rsvp`) — that file only exists on sites, never inside the framework itself.
```

con:

```markdown
- a `docs/ai/PRODUCT.md` exists (or, on sites not yet migrated, a `PRODUCT.md` at the repo root) declaring a Wonder site (`site_type: landing` / `corporate` / `blog` / `ecom` / `rsvp`) — that file only exists on sites, never inside the framework itself.
```

- [ ] **Step 4: `README.md`**

Riga 35, sostituire:

```markdown
Full schema and `site_type` semantics for `PRODUCT.md`: [`skills/wi-site/references/product-md.md`](skills/wi-site/references/product-md.md). Copy-paste template: [`skills/wi-site/references/examples.md` §6](skills/wi-site/references/examples.md#6-productmd--brand-and-project-context).
```

con:

```markdown
Full schema and `site_type` semantics for `docs/ai/PRODUCT.md`: [`skills/wi-site/references/product-md.md`](skills/wi-site/references/product-md.md). Copy-paste template: [`skills/wi-site/references/examples.md` §6](skills/wi-site/references/examples.md#6-docsaiproductmd--brand-and-project-context).

External design skills (`design-taste-frontend`, `redesign-existing-projects`, `image-to-code` from `Leonxlnx/taste-skill`, `web-design-guidelines` from `vercel-labs/agent-skills`, `playwright-cli`) are not bundled: `wi-site` installs the one a task needs, on demand, with `--global`. Routing, commands and precedence rules: [`skills/wi-site/references/external-skills.md`](skills/wi-site/references/external-skills.md).
```

- [ ] **Step 5: Verifica anchor e vecchi testi su tutto il repo**

```bash
cd /Users/andreamarinoni/Developer/packages/skills
grep -rn '6-productmd--\|the-designmd-policy' --include='*.md' README.md AGENTS.md skills; echo "vecchi anchor (atteso 1): $?"
grep -rn 'impeccable\|at the site root first\|at site root\|root of the site repo\|Do \*\*not\*\* create a `DESIGN.md`' --include='*.md' README.md AGENTS.md skills; echo "vecchi testi (atteso 1): $?"
grep -rn '6-docsaiproductmd--brand-and-project-context' --include='*.md' README.md skills | wc -l
for f in $(grep -rhoE '\]\([a-z-]+\.md#[a-z0-9-]+\)' skills/wi-site/references | sed 's/.*(\(.*\))/\1/' | sort -u); do file=skills/wi-site/references/${f%%#*}; anchor=${f#*#}; [ -f "$file" ] || { echo "manca $file"; continue; }; python3 - "$file" "$anchor" <<'PY'
import re,sys
f,a=sys.argv[1],sys.argv[2]
def slug(t):
    t=re.sub(r'`','',t).strip().lower()
    t=re.sub(r'[^\w\- ]','',t,flags=re.U)
    return t.replace(' ','-')
ok=any(slug(l.lstrip('#').strip())==a for l in open(f) if l.startswith('#'))
print(('ok ' if ok else 'ROTTO ')+f+'#'+a)
PY
done
```

Expected: i due `grep` dei vecchi anchor/testi vuoti con exit 1; `5` link al nuovo anchor di §6; le altre occorrenze di `PRODUCT.md` nel repo (titolo di `product-md.md`, frasi che lo citano per nome senza percorso, la riga `# PRODUCT.md` nel template di `examples.md`, "site root" nei comandi `forge`) restano com'erano, perché non indicano una posizione del file; lo script stampa `ok` per ogni link interno tra i riferimenti (`external-skills.md#...`, `product-md.md#...`, `style-and-lib.md#...`, `examples.md#...`). Se una riga dice `ROTTO`, correggere l'anchor o il titolo prima del commit.

- [ ] **Step 6: Commit**

```bash
git add skills/wi-site/references/examples.md skills/wi-site/references/header-navigation.md skills/wi-app/SKILL.md README.md
git commit -m "$(cat <<'MSG'
docs: PRODUCT.md in docs/ai in examples, header-navigation, wi-app e README; rimando a external-skills

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

---

### Task 6: `AGENTS.md`, `TODO.md` locale, smoke test, PR

**Files:**
- Modify: `AGENTS.md:60-80` (regola TODO.md e "Skill content rules")
- Modify (locale, non in staging): `TODO.md:156-167`

- [ ] **Step 1: `AGENTS.md`**

Nella sezione `### Always put work items in TODO.md`, sostituire il paragrafo:

```markdown
**This is the project-wide convention.** Every time work is identified — open question, residual incongruence, follow-up, idea, deferred refactor, missing example, anything that is not being done in the current turn — write it as an entry in [`TODO.md`](TODO.md). Do not let work items live only in conversation, commit messages, or memory.
```

con:

```markdown
**This is the project-wide convention.** Every time work is identified — open question, residual incongruence, follow-up, idea, deferred refactor, missing example, anything that is not being done in the current turn — write it as an entry in `TODO.md`. Do not let work items live only in conversation, commit messages, or memory.

`TODO.md` is **local and gitignored on purpose** (same convention as `new-site`): always update it, never commit it, not even with `git add -f`. It is the maintainer's working list, not documentation.
```

Nella sezione `### Skill content rules`, dopo l'ultimo bullet `- When two skills share a concern, prefer cross-referencing the canonical file over duplicating content.` aggiungere:

```markdown
- Everything about external skills (taste, redesign, image-to-code, web-design-guidelines, playwright-cli: routing, installation, precedence, reports, readiness check) lives only in `skills/wi-site/references/external-skills.md`. Other files link to it with one line.
```

- [ ] **Step 2: `TODO.md` (locale, non in staging)**

Aprire `TODO.md` e, nella sezione `## Brand & project context (PRODUCT.md + impeccable)`:

1. Rinominare il titolo in `## Brand & project context (PRODUCT.md, DESIGN.md, external skills)`.
2. Dopo la voce `- [x] All link/anchor checks pass after the changes ...` aggiungere:

```markdown
- [x] **Superseded (2026-10-14): `PRODUCT.md` moved to `docs/ai/PRODUCT.md`**; root location is only a fallback on sites not yet migrated (readiness check in `skills/wi-site/references/external-skills.md`). Replaces the "at site repo root" entries above.
- [x] **Superseded (2026-10-14): no-`DESIGN.md` policy replaced by the framework-generated `docs/ai/DESIGN.md`** (`wonder-image/app`, `CssTokens::designMd()`, written only with `Debug::enabled()`). Skills read it, never create or edit it.
- [x] **Superseded (2026-10-14): `impeccable` dropped.** No skill or README cites it any more; its role (UI audit / polish) is covered by `redesign-existing-projects` + `web-design-guidelines` via `external-skills.md`.
- [x] **Decided (2026-10-14): `awesome-design-md` not integrated** — it produces a hand-written `DESIGN.md` from code, which is exactly what the generated file makes redundant and risky (spec D6).
- [x] **Decided (2026-10-14): `image-to-code` narrowed** to "user supplies a mockup → extract structure → map onto lib components"; its image-generation steps are out of scope.
```

3. Sostituire per intero la voce che inizia con `- [ ] Implement the spec \`docs/superpowers/specs/2026-10-14-external-skills-routing-design.md\`` (finisce con `Awaiting plan review and execution method.`) con:

```markdown
- [x] Implement the spec `docs/superpowers/specs/2026-10-14-external-skills-routing-design.md` (plans in `docs/superpowers/plans/2026-10-14-*.md`, one per repo: app → new-site → skills). Landed 2026-10-14.
- [ ] Framework follow-up: deny `docs/` in the `.htaccess` template written by `forge build` as defence in depth (today `DESIGN.md` is only kept out of production by `APP_DEBUG=false` and `deploy.yml` excluding `docs/**`).
- [ ] Framework follow-up: `CssTokens::defaultDesignMd()` for the component catalogue, only if the catalogue ever needs the Markdown projection of the default tokens.
- [ ] new-site follow-up: after the three PRs are merged, bump `wonder-image/app` in `composer.json` / lock so a fresh clone generates `docs/ai/DESIGN.md` on the first `php forge update --local`.
- [ ] Repeat the smoke test below whenever `Leonxlnx/taste-skill` changes major version (flags of `draw.mjs`, folder names under the plugin cache, `--keep` semantics).
```

Non eseguire `git add TODO.md`: `git status --short` non deve mai mostrarlo.

- [ ] **Step 3: Commit di `AGENTS.md`**

```bash
git add AGENTS.md
git status --short
git commit -m "$(cat <<'MSG'
AGENTS.md: TODO.md locale e gitignorato, regole skill esterne solo in external-skills.md

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

Expected: `git status --short` prima del commit mostra solo `M  AGENTS.md`; `TODO.md` assente.

- [ ] **Step 4: Smoke test in `new-site` (spec 9) — passo manuale, lo esegue l'utente**

Un subagent non può eseguirlo: servono due sessioni interattive di Claude Code. Chi esegue il piano prepara la skill (comando sotto) e consegna i due prompt all'utente, poi registra l'esito riportato. Richiede la skill aggiornata e un `new-site` con DB locale. Installare la skill dal branch nella macchina locale (il CLI legge il repo remoto: fare prima il push del branch, Step 5, poi tornare qui), oppure copiare la cartella:

```bash
rm -rf ~/.agents/skills/wi-site && cp -R /Users/andreamarinoni/Developer/packages/skills/skills/wi-site ~/.agents/skills/wi-site
cd /Users/andreamarinoni/Developer/boilerplates/new-site && git status --short && ls docs/ai
```

Expected: `docs/ai` contiene `PRODUCT.md`, `reports/` e (con framework aggiornato e `APP_DEBUG=true`) `DESIGN.md`.

Poi, con Claude Code aperto in `new-site`, due prompt separati:

1. `crea la pagina chi siamo` → la sessione deve: leggere `docs/ai/PRODUCT.md` (e avviare l'intervista se vuoto), leggere `docs/ai/DESIGN.md`, dichiarare `design-taste-frontend` e proporne l'installazione `--global` se manca, usare `--keep palette,type`, chiudere con `playwright-cli` + `web-design-guidelines`, lasciare `docs/ai/reports/<data>-chi-siamo-taste-critique.md`, `...-wdg-review.md`, `...-final-report.md`.
2. `aggiungi un campo al form del backend` → nessuna skill esterna citata né installata, nessun file in `docs/ai/reports/` oltre al `final-report`.

Verifica:

```bash
ls docs/ai/reports/ && git status --short
```

Expected: i report del primo prompt presenti; `git status --short` non mostra nulla sotto `docs/ai/reports/` né `.taste-skill/`. Se un passaggio non avviene, è un difetto di testo della skill: correggere il file responsabile (tabella di routing o riga di `SKILL.md`) e ripetere. Annotare in PR l'esito dei due prompt.

Dopo il merge, ripristinare l'installazione ufficiale della skill al posto della copia:

```bash
rm -rf ~/.agents/skills/wi-site && npx skills add wonder-image/skills --skill wi-site --global
```

- [ ] **Step 5: Push e PR**

```bash
cd /Users/andreamarinoni/Developer/packages/skills
git push -u origin wi-site/external-skills
gh pr create --title "wi-site: routing delle skill esterne, docs/ai/PRODUCT.md e DESIGN.md generato" --body "$(cat <<'MSG'
## Summary
- Nuovo `skills/wi-site/references/external-skills.md`: tabella di routing (taste, redesign, image-to-code, web-design-guidelines, playwright-cli), rilevamento e installazione a richiesta con `--global`, precedenza dei token del sito, passaggio del contesto, fallback senza `DESIGN.md`, report in `docs/ai/reports/`, site readiness check.
- `SKILL.md`: legge `docs/ai/PRODUCT.md` (fallback root + migrazione) e `docs/ai/DESIGN.md`; una riga di routing per pagine pubbliche, chiusura UI e skill esterne.
- `style-and-lib.md`, `product-md.md`, `examples.md`, `header-navigation.md`, `wi-app/SKILL.md`, `README.md`, `AGENTS.md`: percorsi `docs/ai/`, `DESIGN.md` generato (non più "non crearlo"), `impeccable` rimossa.

Spec: `docs/superpowers/specs/2026-10-14-external-skills-routing-design.md`, sezione 6. Terzo dei tre PR (app → new-site → skills); funziona anche con il framework non aggiornato grazie al fallback.

## Test plan
- [ ] nessun vecchio anchor/testo (`grep` di Task 5 Step 5) e tutti i link interni tra i riferimenti risolvono
- [ ] smoke test in `new-site`: "crea la pagina chi siamo" attraversa taste → playwright-cli → web-design-guidelines e lascia i tre report; "aggiungi un campo al form del backend" non tocca skill esterne
- [ ] readiness check su un sito con `PRODUCT.md` in root: `git mv`, `.gitignore`, `deploy.yml`, `docs/ai/reports/` aggiornati, nulla di gitignorato nel commit

🤖 Generated with [Claude Code](https://claude.com/claude-code)
MSG
)"
```

Expected: PR aperta su `wonder-image/skills`, base `main`. Dopo il merge, `npx skills update` sui laptop porta la skill nuova.
