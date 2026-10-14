# Routing delle skill esterne da `wi-site` e cartella `docs/ai/`

Data: 2026-10-14 · Stato: bozza per revisione · Repo coinvolti: `wonder-image/skills`, `wonder-image/app`, `wonder-image/new-site`

## 1. Obiettivo

Quando un agente lavora in un sito Wonder con la skill `wi-site`, deve richiamare la skill esterna giusta per ogni esigenza (design di pagine pubbliche, redesign, trascrizione di un mockup, revisione contro le linee guida Vercel, verifica nel browser) senza che l'utente la nomini. Le skill esterne non vanno nel pacchetto: si installano a richiesta con `--global`, la prima volta che servono.

Tutti i file `.md` che le skill creano o leggono in un sito stanno in una sola cartella, `docs/ai/`, con un ordine fisso. `DESIGN.md` esiste, ma è una proiezione generata dal framework dai token di `root.css` e `color.css`, mai scritta a mano: così non può divergere dal tema reale del sito.

## 2. Decisioni prese

Ogni punto è una decisione già confermata in conversazione o una scelta mia marcata **Decisione**, rivedibile in revisione.

- **D1 · Cartella `docs/ai/`.** Alternative scartate: `docs/` (troppo generica), `docs/skills/` (si confonde con `.agents/skills/`, dove vivono le skill installate).
- **D2 · `DESIGN.md` lo genera il framework.** `wonder-image/app` lo scrive in `docs/ai/DESIGN.md` ogni volta che scrive `root.css`. È gitignorato come `root.css` e `color.css`, perché deriva dal DB.
- **D3 · Decisione: `DESIGN.md` si genera solo in ambiente di sviluppo** (`\Wonder\App\Debug::enabled()` vero, cioè `APP_DEBUG=true` o host locale). In produzione `deploy.yml` forza `APP_DEBUG=false`, quindi il file non compare mai nella webroot pubblica. Alternativa: scriverlo sempre e negare `docs/` nel template `.htaccess` di `forge build`; scartata perché `.htaccess` viene scritto solo se manca e i siti esistenti resterebbero esposti finché non rilanciano `forge build --force`. Il file serve a un agente, e in produzione non ce n'è.
- **D4 · `PRODUCT.md` si sposta in `docs/ai/PRODUCT.md`** ed è committato. I siti esistenti con `PRODUCT.md` in root vengono migrati da `wi-site` con `git mv` la prima volta che la skill li tocca. Finché la migrazione non è fatta, la root resta un fallback di lettura per `wi-site` e un segnale di riconoscimento per `wi-app`.
- **D5 · Installazione a richiesta, `--global`.** Nessuna modifica a `NPX_SKILL` del framework.
- **D6 · `awesome-design-md` non si integra.** Il suo prodotto (un `DESIGN.md` scritto da un agente a partire dal codice) è esattamente ciò che D2 rende superfluo e rischioso. `image-to-code` si integra solo nella parte "l'utente fornisce un mockup → estrai struttura e stile → mappa sui componenti lib"; la generazione di immagini è fuori.
- **D7 · Report in `docs/ai/reports/YYYY-MM-DD-<task>-<tipo>.md`**, gitignorati. I file di lavoro di taste restano in `.taste-skill/`, gitignorata.
- **D8 · Precedenza.** `docs/ai/PRODUCT.md`, i token di `root.css`/`color.css` (come proiettati in `DESIGN.md`) e `references/style-and-lib.md` vincono su qualunque proposta di una skill esterna. Le skill esterne decidono composizione, copy, ritmo e motion; una palette, un font o un CSS nuovo si mappano sui token o si scartano.
- **D9 · Precedenza del framing "USE, do not MODIFY".** Questo lavoro modifica `wonder-image/app`: è una decisione di framework presa dall'utente in questa conversazione, non una deroga che le skill possono riusare.

## 3. La cartella `docs/ai/`

```
docs/ai/
├── PRODUCT.md          # committato · contesto brand/progetto, compilato con l'intervista di wi-site
├── DESIGN.md           # gitignorato · generato da wonder-image/app dai token CSS (solo in sviluppo)
└── reports/            # gitignorata · output delle skill esterne e Required Final Report
    └── YYYY-MM-DD-<task>-<tipo>.md
```

`<tipo>` è uno di: `taste-critique`, `redesign-audit`, `wdg-review` (web-design-guidelines), `final-report`, `inspect`. `<task>` è uno slug breve del compito (`chi-siamo`, `home-hero`).

`.taste-skill/` resta dove taste la crea (root del sito) ed è gitignorata: spostarla richiederebbe patchare gli script di taste.

Nessun altro `.md` di skill fuori da `docs/ai/`. La cartella `docs/` del sito non viene mai deployata (sezione 5).

## 4. `wonder-image/app`: generazione di `DESIGN.md`

### 4.1 Funzione pura

Nuovo metodo in `class/View/CssTokens.php`, accanto a `root()` e `colorClasses()`:

```php
public static function designMd(object $default, array $colors, array $fontFamilies): string
```

Stessi input di `root()` per la parte che interessa (`css_default`, righe `css_color` con `var`/`color`/`contrast`, famiglie font già decodificate). Le righe `css_input`, `css_auth`, `css_modal`, `css_dropdown`, `css_alert` sono escluse: descrivono la cromatura del backend, non il design del brand.

### 4.2 Schema del file generato

```markdown
<!-- Generato da wonder-image/app (CssTokens::designMd) dalle tabelle css_default, css_font, css_color.
     Non modificare a mano: cambia i valori nel backend (CSS → Colori / Font / Impostazioni) o nel seed,
     poi `php forge update --local`. Il file si rigenera a ogni salvataggio. -->

# Design tokens

Proiezione leggibile di `assets/{ASSETS_VERSION}/css/set-up/root.css` e `color.css`.
Le variabili e le classi elencate sono le uniche fonti di colore e tipografia ammesse nel sito.

## Palette

| Ruolo | Colore | Contrasto (`-o`) | Variabili |
|---|---|---|---|
| primary | #RRGGBB | #RRGGBB | `--primary-color`, `--primary-o-color`, `--primary-color-{0..100 passo 10}`, `--primary-o-color-{0..100 passo 10}` |
| … una riga per ogni riga di css_color, nell'ordine della tabella … |

Testo di default: `--tx-color` #RRGGBB (`--tx-color-{0..100 passo 10}`) · Sfondo di default: `--bg-color` #RRGGBB (`--bg-color-{0..100 passo 10}`)

## Tipografia

| Gruppo | Famiglia | Peso | Dimensione | Interlinea | Variabili |
|---|---|---|---|---|---|
| default | … | … | …px | …px | `--font-family`, `--font-weight`, `--font-size`, `--line-height` |
| title-big | … | … | … | … | `--title-big-*` |
| title | … | | | | `--title-*` |
| subtitle | … | | | | `--subtitle-*` |
| text | … | | | | `--text-*` |
| text-small | … | | | | `--text-small-*` |

## Spaziatura e layout

- `--spacer`: …px
- `--header-height`: …px

## Bottoni e badge

| Componente | Font size | Line height | Peso | Radius | Bordo |
|---|---|---|---|---|---|
| button | …px | …px | … | …px | …px |
| badge | …px | …px | … | …px | …px |

## Classi utility (da color.css)

Per ogni ruolo `{r}` della palette (primary, secondary, …):
`.tx-{r}` `.tx-{r}-o` `.bg-{r}` `.bg-{r}-o` `.bg-{r}-{0..100 passo 10}` `.bg-{r}-o-{N}` `.tx-stroke-{r}`
`.btn-{r}` `.btn-{r}-o` `.badge-{r}` `.badge-{r}-o` e le varianti `hover\:` di `tx-`/`bg-`.

## Come usarlo

- Un colore o un font proposto da una skill esterna si mappa su un ruolo o un gruppo qui sopra, oppure si scarta.
- Voce, tono e personalità del brand non stanno qui: vedi `docs/ai/PRODUCT.md`.
```

I gruppi tipografici ripetono le variabili per esteso nella tabella reale (`--title-font-family`, `--title-font-weight`, `--title-font-size`, `--title-line-height`, ecc.); l'abbreviazione `--title-*` sopra è solo per compattezza della spec. Le percentuali di alpha e le unità seguono esattamente `root()`.

### 4.3 Hook di scrittura

In `app/function/other/css.php`, `cssRoot($update = true)`: dopo aver scritto `root.css`, se `$update` è vero e `\Wonder\App\Debug::enabled()` è vero:

1. `mkdir($ROOT.'/docs/ai', 0777, true)` se non esiste (i salvataggi dal backend non passano dalla `mkdir` di `app/build/update/css.php`).
2. scrive `$ROOT.'/docs/ai/DESIGN.md'` con `CssTokens::designMd($CSS_DEFAULT, sqlSelect('css_color')->row, $fontFamilies)`, riusando gli stessi valori già calcolati per `root()`.

`cssRoot(false)` resta invariato (ritorna la stringa CSS). `cssColor()` non cambia: ogni punto che chiama `cssColor()` chiama anche `cssRoot()`, quindi un solo hook copre tutto il ciclo di vita:

| Trigger | File |
|---|---|
| salvataggio font nel backend | `class/App/Resources/Css/CssFontResource.php:176-181` |
| salvataggio colore nel backend | `class/App/Resources/Css/CssColorResource.php:130-139` |
| salvataggio impostazioni CSS nel backend | `class/App/Resources/Support/CssSingleton.php:62-67` |
| `php forge import` | `class/Console/Commands/Import.php:122` |
| `php forge update` (locale e `/api/app/update/`) | `app/build/update/css.php:7` |

Errori di scrittura (cartella non scrivibile) non devono far fallire il salvataggio del CSS: si logga con il `Logger` esistente e si prosegue.

### 4.4 Test

`tests/View/CssTokensTest.php` riceve un caso che costruisce gli input dalle righe di `SeedDefaults` e dalla palette di `RuntimeDefaults` (come fa `defaultRoot()`) e verifica che `designMd()`:

- contenga il commento di testa "Generato da wonder-image/app";
- abbia una riga di palette per ogni `var` delle righe colore, con hex colore e hex contrasto;
- abbia le sei righe tipografiche con le famiglie passate;
- riporti `--spacer` e `--header-height` con i valori del seed;
- non contenga segnaposto non sostituiti (`{$`, `…`): i puntini nello schema di 4.2 sono solo illustrativi, il file reale ha i valori.

Il gate `Debug::enabled()` e la scrittura su disco non si testano a unità: sono tre righe in una funzione legacy globale senza harness.

### 4.5 Documentazione e changelog

- `CHANGELOG.md` → `## Unreleased / ### Added`: una voce che descrive `docs/ai/DESIGN.md`, quando si rigenera e il gate di sviluppo.
- `docs/app/piattaforma/asset-e-cache.md` e `docs/app/piattaforma/multi-ambiente.md`: dove descrivono la rigenerazione di `root.css`/`color.css`, aggiungere che in sviluppo si rigenera anche `docs/ai/DESIGN.md` e che il file è gitignorato e non va modificato a mano.
- `AGENTS.md` del framework non cambia: la cartella `docs/ai/` è del sito, non del framework.

## 5. `wonder-image/new-site`: scaffold

- Creare `docs/ai/PRODUCT.md` con il template di `references/examples.md` §6 (sezioni vuote: il file deve esistere per segnalare la convenzione, l'intervista di `wi-site` lo compila). Creare `docs/ai/reports/.gitkeep`.
- `.gitignore`: aggiungere sotto "Generated/local site configuration":
  ```
  /docs/ai/DESIGN.md
  /docs/ai/reports/*
  !/docs/ai/reports/.gitkeep
  /.taste-skill/
  ```
- `.github/workflows/deploy.yml`, lista `exclude` del passo FTP: aggiungere `docs/**`. Erratum (review piano 2): FTP-Deploy-Action confronta `exclude` con `matchBase`, quindi il pattern `*.md` esistente esclude già ogni `.md` a qualsiasi profondità, `docs/ai/PRODUCT.md` compreso; `docs/**` serve per i file non-md sotto `docs/` e per le cartelle stesse.
- `AGENTS.md`: aggiungere `docs/ai/` all'albero della struttura e una riga nella sezione "AI skills" che rimanda a `docs/ai/PRODUCT.md` come primo file da leggere.
- `README.md`: aggiornare l'eventuale riferimento a `PRODUCT.md` in root (oggi il file non esiste nello scaffold, quindi probabilmente nessun riferimento).

Queste modifiche raggiungono solo i siti nuovi. Per i siti esistenti vale la sezione 6.3.

## 6. `wonder-image/skills`

### 6.1 Nuovo riferimento: `skills/wi-site/references/external-skills.md`

Unico posto canonico per tutto ciò che riguarda le skill esterne. Sezioni:

**Tabella di routing**

| Esigenza | Skill | Sorgente | Scope in un sito Wonder |
|---|---|---|---|
| Pagina pubblica nuova, hero, landing, sezione con impatto visivo | `design-taste-frontend` | `Leonxlnx/taste-skill` | solo frontend pubblico; mai backend, dashboard, form di amministrazione |
| "Rifai / migliora / modernizza" una pagina pubblica esistente | `redesign-existing-projects` | `Leonxlnx/taste-skill` | audit → diagnosi → fix nello stack esistente (PHP + lib), niente riscritture |
| L'utente fornisce un mockup o uno screenshot da riprodurre | `image-to-code` | `Leonxlnx/taste-skill` | estrarre struttura, gerarchia, spaziature; mappare sui componenti `wonder-image/lib`; niente generazione di immagini |
| Revisione di accessibilità, UX, performance percepita, copy UI | `web-design-guidelines` | `vercel-labs/agent-skills` | su file frontend; output `file:line` nel report |
| Vedere la pagina, screenshot a 375/768/1280, console | `playwright-cli` | `npm i -g @playwright/cli@latest` + `playwright-cli install --skills` | ogni task che tocca HTML/CSS pubblico, prima del Required Final Report |

**Rilevamento e installazione.** Prima di usare una skill: cercare `~/.agents/skills/<name>/SKILL.md` o `~/.claude/skills/<name>/SKILL.md`; per taste anche `~/.claude/plugins/cache/taste-skill/*/skills/*/SKILL.md` (installazione come plugin). Se manca, dire all'utente cosa si sta per installare e lanciare:

```bash
npx skills add Leonxlnx/taste-skill --skill design-taste-frontend --global
npx skills add Leonxlnx/taste-skill --skill redesign-existing-projects --global
npx skills add Leonxlnx/taste-skill --skill image-to-code --global
npx skills add vercel-labs/agent-skills --skill web-design-guidelines --global
npm i -g @playwright/cli@latest && playwright-cli install --skills
```

I nomi `--skill` vanno verificati una volta con `npx skills add Leonxlnx/taste-skill --list` (il nome è quello del frontmatter, non della cartella). Si installa solo la skill che serve al task, non il pacchetto. `--global` perché l'installazione è dello sviluppatore, non del sito: niente `.agents/` o `skills-lock.json` nel repo del sito. Requisiti di taste (Node 22+, Chrome) si verificano al primo uso e si riportano se mancano.

**Precedenza (D8)**, scritta per esteso, con i tre casi concreti: taste propone una palette → si usa `--keep palette,type` e si mappano i ruoli su `DESIGN.md`; web-design-guidelines propone un fix CSS → si applica con utility lib o token, mai con valori hardcoded; image-to-code estrae un font dal mockup → si usa il gruppo tipografico più vicino e si segnala la differenza nel report.

**Come si passa il contesto alle skill esterne.** Verificato negli script di taste: `--keep palette,type` non legge nessun file, azzera soltanto l'estrazione di palette e tipografia e lascia all'agente la mappatura. Quindi `wi-site` legge `docs/ai/DESIGN.md` e `docs/ai/PRODUCT.md` e trascrive nel `--brief`, nel `--sector` e nel `--tone` ciò che serve; i ruoli colore e i gruppi font si mappano a mano sulle sezioni dealt. Lo stesso vale per `redesign-existing-projects` e `image-to-code`: `DESIGN.md` è contesto per l'agente, non un input degli script.

**Fallback senza `DESIGN.md`** (sezione 7): se `docs/ai/DESIGN.md` manca, lanciare `php forge update --local` una volta; se manca ancora il framework del sito è precedente a questa funzione: leggere direttamente `assets/{ASSETS_VERSION}/css/set-up/root.css` e `color.css` e dire all'utente che `composer update wonder-image/app` porterà il file. Mai scrivere `DESIGN.md` a mano.

**Report.** Ogni skill esterna scrive il suo output in `docs/ai/reports/` con il nome di D7. Il Required Final Report di `style-and-lib.md` diventa anch'esso un file lì (`<tipo>` = `final-report`), oltre al riassunto in chat.

**Site readiness check** (6.3), eseguito la prima volta che `wi-site` tocca un sito in una sessione.

### 6.2 Modifiche a `skills/wi-site/SKILL.md`

- Frontmatter, riga 7: `PRODUCT.md` → `docs/ai/PRODUCT.md`; aggiungere `docs/ai/` ai trigger.
- Start Sequence, passo 0: leggere `docs/ai/PRODUCT.md` (fallback `PRODUCT.md` in root → migrare, 6.3). Aggiungere passo 0b: leggere `docs/ai/DESIGN.md` se il task tocca UI (fallback in 6.1).
- Task Routing, "Brand, copywriting, voice, site type": togliere la frase "Do not create a `DESIGN.md`"; sostituire con "`docs/ai/DESIGN.md` è generato dal framework: leggilo, non modificarlo".
- Task Routing, "UI / styling / design-system" e "Page or component": una riga ciascuna che rimanda a `external-skills.md` con la condizione di attivazione (pagina pubblica nuova → taste; pagina esistente da rifare → redesign; mockup fornito → image-to-code; sempre, prima di chiudere → playwright-cli + web-design-guidelines).
- Nuova voce di routing "Skill esterne": una riga, punta a `external-skills.md`. Le regole stanno solo lì (nessuna duplicazione).

### 6.3 Site readiness check (in `external-skills.md`, richiamato da SKILL.md passo 0)

Una lista da verificare e sistemare in silenzio, riportando nel Required Final Report ciò che è stato toccato:

1. `PRODUCT.md` in root e `docs/ai/PRODUCT.md` assente → `git mv PRODUCT.md docs/ai/PRODUCT.md`.
2. `.gitignore` senza le quattro righe di sezione 5 → aggiungerle in coda al blocco "Generated/local site configuration" (o in coda al file se il blocco manca).
3. `.github/workflows/deploy.yml` con lista `exclude` senza `docs/**` → aggiungere la riga dopo `*.md`. Se il file manca o non usa FTP-Deploy-Action, segnalare e non toccare.
4. `docs/ai/reports/` assente → crearla con `.gitkeep`.

### 6.4 `skills/wi-site/references/style-and-lib.md`

- Riga 102, regola "no DESIGN.md": sostituire con la regola "`docs/ai/DESIGN.md` è la proiezione dei token; i valori arrivano da lì o da `root.css`/`color.css`, mai da una skill esterna".
- "Pre-Submit Checklist": aggiungere due voci. Screenshot con `playwright-cli` a 375/768/1280 e console pulita; revisione `web-design-guidelines` sui file toccati, findings risolti o motivati nel report.
- "Required Final Report": aggiungere che il report si salva in `docs/ai/reports/YYYY-MM-DD-<task>-final-report.md` e che elenca gli screenshot e il file di findings.

### 6.5 `skills/wi-site/references/product-md.md`

- "Where it lives" (22–38): `docs/ai/PRODUCT.md`; nota sulla migrazione da root; togliere la frase che vieta `docs/`.
- "The DESIGN.md policy" (117–125): riscrivere come "DESIGN.md generato" con un rimando a `external-skills.md` per la precedenza. Niente procedura qui: una sola fonte.
- Procedura di intervista: il salvataggio avviene in `docs/ai/PRODUCT.md`.

### 6.6 `skills/wi-site/references/examples.md`

- §6: titolo e percorso del template → `docs/ai/PRODUCT.md`.
- Riga 431: il bullet "no DESIGN.md" diventa "leggi `docs/ai/DESIGN.md`, non crearlo né modificarlo".

### 6.7 `skills/wi-site/references/header-navigation.md:193`

Sostituire il check manuale nel browser con il rimando a `playwright-cli` in `external-skills.md`.

### 6.8 `skills/wi-app/SKILL.md`

Riga 44: il segnale diventa "esiste `docs/ai/PRODUCT.md` (o, nei siti non ancora migrati, `PRODUCT.md` in root)". Nessun'altra modifica: la generazione di `DESIGN.md` è interna al framework e documentata nel suo CHANGELOG, non nella skill.

### 6.9 `README.md`, `TODO.md`, `AGENTS.md` del repo skills

- `README.md`: riga 35 → `docs/ai/PRODUCT.md`; aggiungere una riga che spiega che le skill esterne si installano a richiesta con `--global` e rimanda a `external-skills.md`.
- `TODO.md` (locale e gitignorato di proposito, come in `new-site`: si aggiorna, non si committa): nella sezione "Brand & project context" aggiungere una voce che supera `:163` (politica no-DESIGN.md sostituita dal file generato, con data); voci nuove per: `awesome-design-md` scartata (motivo D6), `image-to-code` ristretto, `impeccable` abbandonata (voce `:161` superata, nessuna skill la cita più), follow-up framework (deny `docs/` in `.htaccess` come difesa in profondità, `defaultDesignMd()` per il catalogo se mai servirà), follow-up new-site, smoke test da ripetere quando taste cambia major.
- `AGENTS.md`: nessuna modifica di layout (nessuna cartella `scripts/`). Aggiungere una riga in "Skill content rules": le regole sulle skill esterne stanno solo in `external-skills.md`. Nella regola su `TODO.md` precisare che il file è locale e gitignorato di proposito: si aggiorna sempre, non si committa mai, nemmeno con `git add -f`.

## 7. Ordine di consegna e compatibilità tra versioni

Le skill arrivano a ogni sito via `npx skills add`; il framework arriva via Composer alla versione che ogni sito blocca. Le due cose non si aggiornano insieme, quindi:

1. **Prima `wonder-image/app`** (sezione 4), con test e CHANGELOG. Senza questo, `DESIGN.md` non esiste da nessuna parte.
2. **Poi `wonder-image/new-site`** (sezione 5): i siti nuovi nascono già in ordine.
3. **Infine `wonder-image/skills`** (sezione 6), scritte per funzionare anche sui siti che non hanno ancora il framework aggiornato: il fallback di 6.1 legge `root.css`/`color.css` direttamente.

Ogni repo ha il proprio branch e la propria PR; questa spec genera un piano per repo, eseguiti nell'ordine sopra.

## 8. Flussi end-to-end

**"Crea la pagina chi siamo".** `wi-site` legge `docs/ai/PRODUCT.md` e `docs/ai/DESIGN.md`, fa il readiness check, riconosce "pagina pubblica nuova" → verifica/installa `design-taste-frontend` → `draw.mjs --sector … --brief … --keep palette,type --have …` → mappa i ruoli dealt sulla palette e i gruppi font di `DESIGN.md` → costruisce la pagina con componenti lib → `playwright-cli` screenshot + console → `web-design-guidelines` sui file toccati → scrive `docs/ai/reports/2026-10-14-chi-siamo-taste-critique.md`, `…-wdg-review.md`, `…-final-report.md` → riassunto in chat.

**"Aggiungi un campo al form del backend".** Nessuna skill esterna: il routing di taste e redesign è limitato al frontend pubblico. Solo il Required Final Report.

**"La home è brutta, miglioriamola".** Pagina esistente → `redesign-existing-projects`: audit con `lint.mjs`/`inspect.mjs`, diagnosi, fix nello stack esistente, palette e font invariati, report `…-redesign-audit.md`, poi la stessa chiusura del primo flusso.

## 9. Verifica

- Framework: `tests/View/CssTokensTest.php` verde; `php forge update --local` in `new-site` produce `docs/ai/DESIGN.md` coerente con `root.css` (confronto a mano dei valori di primary e dei sei font); con `APP_DEBUG=false` il file non viene scritto.
- Comandi di installazione: ogni riga di 6.1 eseguita sulla macchina dell'utente con `--list`/dry run dove disponibile, poi verifica della presenza in `~/.agents/skills/`.
- Smoke test in `~/Developer/boilerplates/new-site` con Claude Code: "crea la pagina chi siamo" deve attraversare il flusso di sezione 8 e lasciare i tre report; "aggiungi un campo al form del backend" non deve toccare taste.
- Readiness check su un sito cliente con `PRODUCT.md` in root: migrazione eseguita, `.gitignore` e `deploy.yml` aggiornati, nulla di gitignorato finisce nel commit.

## 10. Fuori scope

- Patchare gli script di taste (posizione di `.taste-skill/`, lettura diretta di `DESIGN.md`).
- Modificare `NPX_SKILL` o l'auto-installazione del framework.
- `awesome-design-md` e la parte di generazione immagini di `image-to-code`.
- Deny di `docs/` in `.htaccess` (tracciato in `TODO.md` come difesa in profondità).
