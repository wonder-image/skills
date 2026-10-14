# `wonder-image/new-site`: cartella `docs/ai/` nello scaffold — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ogni sito nato da `new-site` ha già `docs/ai/PRODUCT.md` (committato, con le sezioni vuote), `docs/ai/reports/` (gitignorata), le regole `.gitignore` per `DESIGN.md`, i report e `.taste-skill/`, ed esclude `docs/` dal deploy FTP.

**Architecture:** Solo file di scaffold, nessun PHP. Due file nuovi sotto `docs/ai/` e tre ritocchi (`.gitignore`, `deploy.yml`, `AGENTS.md`) in un'unica PR. `DESIGN.md` non si crea qui: lo scrive il framework (piano `2026-10-14-app-design-md-generator.md`) quando il sito aggiorna `wonder-image/app`.

**Tech Stack:** git, YAML del workflow GitHub Actions (`SamKirkland/FTP-Deploy-Action`), Markdown.

**Spec:** `docs/superpowers/specs/2026-10-14-external-skills-routing-design.md` (repo `wonder-image/skills`), sezioni 3 e 5. Secondo dei tre piani (sezione 7): si esegue dopo il merge della PR di `wonder-image/app` e prima di `2026-10-14-wi-site-external-skills.md`.

## Global Constraints

- **Repo e cwd:** `/Users/andreamarinoni/Developer/boilerplates/new-site` (checkout di `wonder-image/new-site`, branch `main`, HEAD `386adae`). Tutti i comandi si lanciano da lì.
- **Il checkout non è pulito e le modifiche non sono nostre:** `git status --short` mostra ` M package-lock.json`, ` M package.json`, ` M shared/sync-data.json`. Non toccarle, non stasharle, non committarle. Per questo ogni `git add` di questo piano elenca i path uno per uno: mai `git add .`, `git add -A`, `git add -u` o `git commit -a`.
- **Branch:** `docs-ai` creato da `main`. Una PR su `main`.
- **`.gitignore`:** le quattro righe vanno aggiunte, in questo ordine, in coda al blocco `# Generated/local site configuration` (dopo `/assets/0.0/css/set-up/root.css`, riga 39):
  ```
  /docs/ai/DESIGN.md
  /docs/ai/reports/*
  !/docs/ai/reports/.gitkeep
  /.taste-skill/
  ```
- **`deploy.yml`:** la riga `docs/**` va dentro la lista `exclude: |` del passo `SamKirkland/FTP-Deploy-Action@v4.3.4`, subito dopo `*.md` (riga 160), con la stessa indentazione di 14 spazi. `*.md` copre solo la root: senza `docs/**` il `PRODUCT.md` committato finirebbe sul server.
- **`docs/ai/PRODUCT.md`:** le intestazioni del template di `skills/wi-site/references/examples.md` §6 con le sezioni vuote. Il file deve esistere per segnalare la convenzione; lo compila l'intervista di `wi-site`. Non inventare contenuti di brand per lo scaffold.
- **`README.md`** di `new-site` non cita `PRODUCT.md` né `docs/` (verificato con `grep`): nessuna modifica.
- **Commit:** ogni messaggio termina con la riga `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`.

## Review Focus

1. **`.gitignore` e `.gitkeep` in conflitto:** `/docs/ai/reports/*` da solo ignorerebbe anche `.gitkeep` e la cartella non arriverebbe nei clone. La negazione `!/docs/ai/reports/.gitkeep` deve stare dopo il pattern che ignora. Verifica con `git check-ignore -v` in Task 1.
2. **`DESIGN.md` committato per sbaglio** da chi lavora in sviluppo con il framework aggiornato: `git check-ignore docs/ai/DESIGN.md` deve rispondere con la regola. Verifica in Task 1.
3. **YAML del workflow rotto** da una riga con indentazione sbagliata dentro il blocco `exclude: |`: lo scalar letterale accetta qualunque testo, quindi un parser YAML non basta a capire se `docs/**` è dentro la lista. Il controllo è `sed -n` sul blocco in Task 2, più il parse.
4. **Pattern `docs/**` troppo largo:** nel sito non devono esistere altre cartelle `docs/` da deployare. `find` in Task 2 lo conferma (`vendor/` non è deployato: arriva dal `composer install` sul server).
5. **`PRODUCT.md` con sezioni vuote letto da `wi-site`:** la skill deve avviare l'intervista invece di proseguire. È comportamento della skill (piano `2026-10-14-wi-site-external-skills.md`); qui basta che le intestazioni siano esattamente quelle del template, così la skill le riconosce. Verifica con `grep -c '^## '` in Task 1.

---

### Task 1: `docs/ai/` e `.gitignore`

**Files:**
- Create: `docs/ai/PRODUCT.md`
- Create: `docs/ai/reports/.gitkeep`
- Modify: `.gitignore:33-39`

**Interfaces:**
- Produces: `docs/ai/PRODUCT.md` con le dieci intestazioni `## Register`, `## Site type`, `## Target users`, `## Brand personality`, `## Voice / tone`, `## Design principles`, `## Anti-references`, `## Asset paths`, `## Constraints`, `## Inspirations` (le stesse che `wi-site` cerca); cartella `docs/ai/reports/` tracciata tramite `.gitkeep`.

- [ ] **Step 1: Branch**

```bash
cd /Users/andreamarinoni/Developer/boilerplates/new-site && git checkout main && git status --short && git checkout -b docs-ai
```

Expected: `git status --short` mostra esattamente le tre righe ` M package-lock.json`, ` M package.json`, ` M shared/sync-data.json` (preesistenti, da lasciare stare); branch `docs-ai` creato.

- [ ] **Step 2: Creare `docs/ai/PRODUCT.md` e `docs/ai/reports/.gitkeep`**

```bash
mkdir -p docs/ai/reports && touch docs/ai/reports/.gitkeep && cat > docs/ai/PRODUCT.md <<'MD'
# PRODUCT.md

<!-- Contesto di brand e progetto letto da wi-site prima di ogni task.
     Sezioni vuote: la prima volta che wi-site lavora su questo sito avvia
     l'intervista e le compila. Schema e semantica di site_type in
     skills/wi-site/references/product-md.md (repo wonder-image/skills). -->

## Register

> `brand` (il design è il prodotto) oppure `product` (il design serve il prodotto).

## Site type

> Uno tra: `landing`, `corporate`, `blog`, `ecom`, `rsvp`.

## Target users

## Brand personality

## Voice / tone

## Design principles

## Anti-references

## Asset paths

- Logo SVG: `assets/{ASSETS_VERSION}/icons/logo.svg`
- Logo PNG (fallback): `assets/{ASSETS_VERSION}/icons/logo.png`
- Favicon: `assets/{ASSETS_VERSION}/icons/favicon.png`
- OG image default: `assets/{ASSETS_VERSION}/images/og-default.jpg`

## Constraints

## Inspirations
MD
grep -c '^## ' docs/ai/PRODUCT.md && ls -la docs/ai docs/ai/reports
```

Expected: `10`, poi `PRODUCT.md` e la cartella `reports/` con `.gitkeep`.

- [ ] **Step 3: `.gitignore`**

Aprire `.gitignore`. Il blocco alle righe 33-39 è:

```
# Generated/local site configuration
/.htaccess
/robots.txt
/handler/index.php
/bin/scheduler.php
/assets/0.0/css/set-up/color.css
/assets/0.0/css/set-up/root.css
```

Subito dopo la riga `/assets/0.0/css/set-up/root.css` (riga 39) e prima della riga vuota che precede `# Runtime/generated content`, inserire:

```
/docs/ai/DESIGN.md
/docs/ai/reports/*
!/docs/ai/reports/.gitkeep
/.taste-skill/
```

Con `sed` (macOS BSD):

```bash
sed -i '' '/^\/assets\/0.0\/css\/set-up\/root.css$/a\
/docs/ai/DESIGN.md\
/docs/ai/reports/*\
!/docs/ai/reports/.gitkeep\
/.taste-skill/
' .gitignore && sed -n '33,44p' .gitignore
```

Expected: le righe 33-43 sono il blocco sopra con le quattro righe nuove in coda, poi la riga vuota e `# Runtime/generated content`.

- [ ] **Step 4: Verificare le regole con git**

```bash
touch docs/ai/DESIGN.md docs/ai/reports/2026-10-14-prova-final-report.md && mkdir -p .taste-skill && touch .taste-skill/x
git check-ignore -v docs/ai/DESIGN.md docs/ai/reports/2026-10-14-prova-final-report.md .taste-skill/x; echo "ignorati: $?"
git check-ignore -v docs/ai/reports/.gitkeep docs/ai/PRODUCT.md; echo "non ignorati (atteso 1): $?"
git status --short docs .taste-skill
rm -rf docs/ai/DESIGN.md docs/ai/reports/2026-10-14-prova-final-report.md .taste-skill
```

Expected: le prime tre righe stampano `.gitignore:40:/docs/ai/DESIGN.md`, `.gitignore:41:/docs/ai/reports/*`, `.gitignore:43:/.taste-skill/` con exit 0; il secondo `check-ignore` non stampa nulla ed esce con 1; `git status --short docs` mostra solo `?? docs/ai/PRODUCT.md` e `?? docs/ai/reports/.gitkeep` (git elenca i file, non `.taste-skill`).

- [ ] **Step 5: Commit**

```bash
git add .gitignore docs/ai/PRODUCT.md docs/ai/reports/.gitkeep
git status --short
git commit -m "$(cat <<'MSG'
docs/ai: PRODUCT.md vuoto, reports/ e regole gitignore per DESIGN.md e taste

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

Expected: `git status --short` prima del commit mostra `M  .gitignore`, `A  docs/ai/PRODUCT.md`, `A  docs/ai/reports/.gitkeep` in staging e le tre modifiche preesistenti ancora ` M` (non in staging). Il commit contiene tre file.

---

### Task 2: `deploy.yml` esclude `docs/`

**Files:**
- Modify: `.github/workflows/deploy.yml:148-160` (lista `exclude: |` del passo `SamKirkland/FTP-Deploy-Action@v4.3.4`)

- [ ] **Step 1: Verificare che non esistano altre cartelle `docs/` deployabili**

```bash
find . \( -path ./node_modules -o -path ./vendor -o -path ./.git \) -prune -o -type d -name docs -print
```

Expected: solo `./docs`.

- [ ] **Step 2: Aggiungere `docs/**` alla lista `exclude`**

La lista attuale (righe 148-160) è:

```yaml
          exclude: |
              **/.git*
              **/.git*/**
              **/.github*
              **/.github/**
              **/.vscode/**
              **/composer.json
              **/composer.lock
              **/package.json
              **/package-lock.json
              **/node_modules/**
              forge
              *.md
```

Dopo la riga `              *.md` (14 spazi + `*.md`, riga 160) inserire `              docs/**` (14 spazi):

```bash
sed -i '' '160s|^\(              \)\*\.md$|\1*.md\
\1docs/**|' .github/workflows/deploy.yml && sed -n '146,163p' .github/workflows/deploy.yml
```

Expected: la lista termina con `*.md`, `docs/**`, riga vuota, `      - name: 🗄️ Update App`. Se `sed` non trova la riga (numero diverso), inserire a mano con l'editor: il pattern da cercare è `*.md` dentro `exclude: |`.

- [ ] **Step 3: Verificare il YAML**

```bash
python3 -c "import yaml,sys; d=yaml.safe_load(open('.github/workflows/deploy.yml')); steps=[s for j in d['jobs'].values() for s in j['steps'] if str(s.get('uses','')).startswith('SamKirkland/FTP-Deploy-Action')]; ex=steps[0]['with']['exclude'].split('\n'); print(ex); sys.exit(0 if 'docs/**' in ex and ex.index('docs/**')==ex.index('*.md')+1 else 1)"; echo "exit: $?"
```

Expected: la lista stampata contiene `'*.md', 'docs/**'` consecutivi ed `exit: 0`. Se `yaml` non è installato (`ModuleNotFoundError`), usare `ruby -ryaml -e 'puts YAML.load_file(".github/workflows/deploy.yml")["jobs"].values.flat_map { |j| j["steps"] }.find { |s| s["uses"].to_s.start_with?("SamKirkland/FTP-Deploy-Action") }["with"]["exclude"]'` e controllare a occhio che `docs/**` segua `*.md`.

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/deploy.yml
git commit -m "$(cat <<'MSG'
deploy: escludi docs/ dall'upload FTP

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

---

### Task 3: `AGENTS.md` e PR

**Files:**
- Modify: `AGENTS.md:13-35` (albero della struttura) e `AGENTS.md:100-122` (sezione "AI skills")

- [ ] **Step 1: Albero della struttura**

Nella lista delle cartelle (righe 13-35), dopo l'ultima voce

```markdown
- `shared/sitemap/` — generated runtime sitemap; gitignored
```

aggiungere:

```markdown
- `docs/ai/` — context for AI skills: `PRODUCT.md` (committed, brand/project context),
  `DESIGN.md` (generated by `wonder-image/app` from the CSS tokens in development, gitignored),
  `reports/` (external-skill output, gitignored). Never deployed (`docs/**` is excluded in `deploy.yml`).
```

- [ ] **Step 2: Sezione "AI skills"**

Sostituire il blocco di installazione (righe 112-117):

````markdown
```bash
npx skills add pbakaus/impeccable     # UI design audit/craft skill
npx skills list
npx skills update                     # update all
npx skills remove <slug>
```
````

con:

````markdown
```bash
npx skills add wonder-image/skills --skill wi-site   # Wonder site skill (pages, models, UI, lib)
npx skills list
npx skills update                                    # update all
npx skills remove <slug>
```

`wi-site` reads `docs/ai/PRODUCT.md` first on every task: keep it filled in (the
skill runs an interview when sections are empty). External design skills
(`design-taste-frontend`, `web-design-guidelines`, `playwright-cli` and the other
taste-skill entries) are installed on demand by `wi-site` with `--global`, on the
developer's machine, never into this repo: see `references/external-skills.md`
in the `wi-site` skill.
````

Il paragrafo successivo ("Each dev runs `npx skills add` once on their own laptop. ...") resta.

- [ ] **Step 3: Verifica**

```bash
grep -n 'impeccable' AGENTS.md README.md; echo "impeccable (atteso 1): $?"
grep -n 'docs/ai' AGENTS.md
```

Expected: nessuna riga con `impeccable` ed exit 1; almeno due righe con `docs/ai` (albero e sezione skill).

- [ ] **Step 4: Commit, push, PR**

```bash
git add AGENTS.md
git commit -m "$(cat <<'MSG'
AGENTS.md: cartella docs/ai e skill wi-site al posto di impeccable

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
git status --short
git push -u origin docs-ai
gh pr create --title "Cartella docs/ai nello scaffold" --body "$(cat <<'MSG'
## Summary
- `docs/ai/PRODUCT.md` vuoto (lo compila l'intervista di `wi-site`) e `docs/ai/reports/.gitkeep`.
- `.gitignore`: `docs/ai/DESIGN.md` (generato da `wonder-image/app` in sviluppo), `docs/ai/reports/*`, `.taste-skill/`.
- `deploy.yml`: `docs/**` escluso dall'upload FTP (`*.md` copriva solo la root).
- `AGENTS.md`: `docs/ai/` nell'albero; install di `wi-site` al posto di `impeccable`; skill esterne con `--global`.

Spec: `wonder-image/skills` → `docs/superpowers/specs/2026-10-14-external-skills-routing-design.md`, sezione 5. Secondo dei tre PR (app → new-site → skills).

## Test plan
- [ ] `git check-ignore -v docs/ai/DESIGN.md docs/ai/reports/x.md .taste-skill/x` risponde con le regole; `docs/ai/reports/.gitkeep` e `docs/ai/PRODUCT.md` non sono ignorati
- [ ] `deploy.yml` parsa e `docs/**` segue `*.md` in `exclude`
- [ ] dopo `composer update wonder-image/app` (con la PR del framework mergiata) `php forge update --local` scrive `docs/ai/DESIGN.md` e `git status` non lo mostra

🤖 Generated with [Claude Code](https://claude.com/claude-code)
MSG
)"
```

Expected: `git status --short` mostra ancora solo le tre modifiche preesistenti; PR aperta su `wonder-image/new-site`, base `main`.

- [ ] **Step 5 (dopo il merge della PR di `wonder-image/app`): verifica end-to-end**

Solo quando la PR di `2026-10-14-app-design-md-generator.md` è su `main` del framework:

```bash
cd /Users/andreamarinoni/Developer/boilerplates/new-site
composer update wonder-image/app
php forge update --local
ls -la docs/ai/DESIGN.md && git status --short docs
```

Expected: `DESIGN.md` esiste (serve `APP_DEBUG=true` in `.env`), `git status --short docs` è vuoto. `composer update` modifica `composer.lock`: committarlo nella PR solo se il lock è versionato e l'aggiornamento del framework era voluto, altrimenti lasciarlo fuori e dirlo.
