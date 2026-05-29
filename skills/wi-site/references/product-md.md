# `PRODUCT.md` — brand & project context per un sito Wonder

## Contents

- [Purpose](#purpose)
- [Where it lives](#where-it-lives)
- [Schema](#schema)
- [`site_type` field — semantica](#site_type-field--semantica)
- [The DESIGN.md policy](#the-designmd-policy)
- [How wi-site uses it](#how-wi-site-uses-it)
- [How impeccable uses it](#how-impeccable-uses-it)
- [Compilation procedure (interview-driven)](#compilation-procedure-interview-driven)
- [Template](#template)

## Purpose

Un sito Wonder è "come" — placement dei file, helper, design system già definiti dal framework. `PRODUCT.md` aggiunge il "per chi e perché": brand voice, target users, principi di design, asset paths, e — soprattutto — la **tipologia di sito** (`site_type`) che modula tutto il resto.

Senza `PRODUCT.md` un assistant può solo seguire le convenzioni Wonder. Con `PRODUCT.md`, l'output diventa coerente con il brand e con la natura del progetto (una landing di conversione vs un sito corporate vs un blog vs un ecom si scrivono e si presentano in modo diverso).

`PRODUCT.md` è la convenzione che `wi-site` legge come primo step e che la skill esterna [impeccable](https://github.com/pbakaus/impeccable) consuma nativamente.

## Where it lives

**A root del repo del sito.** Stesso livello di `composer.json`, `package.json`, `README.md`. Versionato con git.

```
my-wonder-site/
├── PRODUCT.md          ← qui
├── composer.json
├── package.json
├── custom/
├── app/
├── lang/
└── assets/
```

Una sola istanza per sito. Non si crea sotto `custom/config/` o `docs/` — impeccable cerca prima a cwd e wi-site segue la stessa convenzione per evitare configurazione extra.

## Schema

`PRODUCT.md` è un file markdown con sezioni canoniche. Ogni sezione è 1–3 righe — se devi scriverne mezza pagina, probabilmente stai mischiando contenuti che vanno altrove (copy = `lang/{locale}/*.json`; token visivi = `assets/{ASSETS_VERSION}/css/set-up/`).

### Sezioni richieste

- **Register** — una sola parola: `brand` (il design ESPRIME il prodotto, es. studio creativo, agenzia di moda) o `product` (il design SERVE il prodotto, es. SaaS, ecom, tool). Vocabolario di impeccable.
- **Site type** — uno dei 5 valori in [`site_type` field — semantica](#site_type-field--semantica).
- **Target users** — chi userà il sito, in una frase. *"Coppie 25–40 anni che cercano un fotografo per matrimoni in Toscana."*
- **Brand personality** — 3–5 aggettivi. *"Caldo, autoriale, raffinato, mai aulico, mai stocky."*
- **Voice / tone** — come parla il sito. *"Italiano, prima persona singolare, frasi corte, niente claim. Nei legali, terza persona neutra."*
- **Design principles** — 3–5 principi che guidano le scelte UI. *"Foto in primo piano, testo solo quando necessario; lettura mobile-first; niente animazioni gratuite."*

### Sezioni opzionali ma utili

- **Anti-references** — cosa NON essere. *"Niente photography stocky di matrimoni patinati; niente font script; niente musica autoplay."*
- **Asset paths** — logo, favicon, OG image, illustrazioni primarie. Path relativi a `assets/`. *Esempio:*
  ```
  - Logo SVG: assets/{ASSETS_VERSION}/icons/logo.svg
  - Favicon: assets/{ASSETS_VERSION}/icons/favicon.png
  - OG default: assets/{ASSETS_VERSION}/images/og-default.jpg
  ```
- **Constraints** — vincoli reali su contenuti o tecnologia. *"Cliente non vuole carrello pubblico; checkout solo via WhatsApp."*
- **Inspirations** — 2–3 reference link che esprimono il vibe (non da copiare, da capire).

## `site_type` field — semantica

Il valore di `site_type` cambia che cosa la skill suggerisce e che cosa **NON** suggerisce. Cinque valori canonici:

### `landing`

Single-page o very-few-page focus. Hero + sezioni narrative + multi-CTA verso una singola conversione. Tipicamente nessun backend custom oltre form contatti. Nessun modulo verticale.

- Pagine: tutte in `custom/view/pages/frontend/`
- Moduli Composer: nessuno (di solito)
- Wi-site evita di proporre Resource backend o CRUD se non strettamente necessari
- Impeccable critique pesa la **conversione** sopra la profondità informativa

### `corporate`

Multi-page site: home, chi-siamo, servizi, contatti, legal. È la forma di default dello scaffold `wonder-image/new-site`. Niente modulo verticale specifico.

- Pagine: `custom/view/pages/frontend/`
- Moduli Composer: nessuno (di solito)
- Backend solo per content backend-editable (es. `app/Models/Service.php` + Resource)
- Default reasonable per la maggior parte dei siti istituzionali

### `blog`

Sito orientato a contenuti editoriali. Abilitare il modulo `wonder-image/blog` in `custom/config/modules.php`.

- Pagine post/listing/categoria/tag: vengono dal modulo
- Override: solo `custom/view/components/frontend/...` mirati a riconnotare la presentazione
- Wi-site propone `wonder-image/blog` come prima cosa, non un Model `Post` custom
- Impeccable critique pesa **leggibilità** (line length, type scale, contrast) sopra il visual flash

### `ecom`

Catalogo + carrello + checkout. Abilitare il modulo `wonder-image/ecom` (quando disponibile) in `custom/config/modules.php`.

- Pagine prodotto/listing/cart/checkout: dal modulo
- Override: solo per riconnotare la presentazione
- Wi-site propone `wonder-image/ecom`, non un Model `Product` custom
- Impeccable critique pesa **trust signals**, **friction-checkout**, **gallery quality** sopra l'editorial

### `rsvp`

Invitati + conferme + gestione lista. Abilitare il modulo `wonder-image/rsvp` (quando disponibile) in `custom/config/modules.php`.

- Pagine form RSVP / dashboard organizzatore: dal modulo
- Override: solo per riconnotare la presentazione e localizzare i form
- Wi-site propone `wonder-image/rsvp`, non un Model `Invitation` custom

### Quando `site_type` non corrisponde

Se il progetto è davvero ibrido (un blog *dentro* un sito corporate, una landing che vende un singolo prodotto), scegli il `site_type` **dominante** — quello che descrive la maggior parte delle pagine e dei flussi. Documenta le eccezioni nella sezione **Constraints** del `PRODUCT.md`.

## The DESIGN.md policy

**Non creare un `DESIGN.md` a root del sito.** I token visivi vivono in:

- `assets/{ASSETS_VERSION}/css/set-up/color.css` — palette colori
- `assets/{ASSETS_VERSION}/css/set-up/root.css` — typography, spacing, radii, shadows
- [`style-and-lib.md`](style-and-lib.md) — il rulebook completo (USE vs MODIFY, naming, custom CSS/JS placement)

Questi sono la **single source of truth**. `DESIGN.md` creerebbe una seconda sorgente con rischio di drift — impossibile mantenere coerente nel tempo.

Impeccable degrada gracefully quando `DESIGN.md` manca: usa solo `PRODUCT.md` per il contesto strategico e legge i CSS reali per il visivo. Confermato dalla doc di impeccable — `DESIGN.md` è "optional, strongly encouraged" per progetti generici; per Wonder sites è **deliberatamente assente**.

## How wi-site uses it

Il primo step di `wi-site` su qualsiasi task di un sito Wonder è: leggere `PRODUCT.md` a root. Tutto quello che segue — placement, copy, scelte di stile, suggerimenti di moduli — deve essere coerente con quanto dichiarato lì.

Il `site_type` in particolare cambia il workflow:

- Una richiesta "aggiungi una pagina prodotti" su `site_type: blog` → wi-site chiede prima conferma (sembra un'azione fuori scope per un blog).
- Una richiesta "aggiungi un articolo" su `site_type: ecom` senza modulo blog attivo → wi-site suggerisce di abilitare `wonder-image/blog` o di confermare se davvero non serve.
- Una richiesta "aggiungi una pagina /shop" su `site_type: corporate` senza modulo ecom → wi-site chiede se è il momento di passare a `site_type: ecom` + modulo `wonder-image/ecom`.

Quando `PRODUCT.md` manca, wi-site lavora comunque — segue solo le convenzioni Wonder generiche. Suggerisce di crearlo come primo step su qualsiasi sito nuovo.

## How impeccable uses it

[Impeccable](https://github.com/pbakaus/impeccable) (`npx skills add pbakaus/impeccable`) è la skill complementare per critique / polish / audit UI profondo. Il suo loader cerca `PRODUCT.md` nell'ordine: env `IMPECCABLE_CONTEXT_DIR`, cwd, `.agents/context/`, `docs/`. Per Wonder sites la convenzione è cwd (root del repo) — nessuna config extra.

Impeccable legge `PRODUCT.md` per:

- Capire register e brand personality (decisioni di density, motion, typography weight)
- Capire target users (vocabolario delle critique)
- Modulare il critique sul `site_type` (es. su una landing pesa di più la conversione; su un blog la leggibilità)

Quando `DESIGN.md` non esiste (situazione attesa nei Wonder sites), impeccable usa solo `PRODUCT.md` + lettura diretta dei CSS reali del progetto. Niente errori, niente warning.

## Compilation procedure (interview-driven)

**Hard rule.** Quando un sito Wonder non ha `PRODUCT.md`, o quando una delle sue sezioni richieste è vuota, `wi-site` **non procede con il task dell'utente** finché non ha raggiunto **almeno l'80% di chiarezza sul progetto**. La soglia è operazionalizzata così:

- **Tutte e 6 le sezioni richieste compilate**: `Register`, `Site type`, `Target users`, `Brand personality`, `Voice / tone`, `Design principles`
- **Almeno una delle sezioni raccomandate compilata**: `Anti-references` *oppure* `Asset paths`

Le sezioni opzionali (`Constraints`, `Inspirations`) possono restare vuote — l'utente le aggiungerà quando il progetto le richiede.

### Perché un'intervista e non un template a freddo

Il template di [`examples.md` §6](examples.md#6-productmd--brand-and-project-context) ha lo scaffolding ma le sezioni sono astratte. Un utente che lo riempie da zero, da solo, in genere scrive frasi vaghe (*"target: utenti interessati"*, *"voce: professionale ma friendly"*) che non producono direzione concreta. L'intervista forza risposte specifiche perché:

- Le domande sono mirate ed esemplificate
- L'assistant può fare follow-up quando la risposta è generica
- L'assistant può inferire da risposte parziali (es. l'utente dice "un sito per il mio studio di fotografia di matrimoni" → `register: brand`, `site_type: corporate`, `target_users: coppie che cercano un fotografo`, tutto da una frase)

Il costo: 10–15 minuti la prima volta. Il guadagno: ogni decisione successiva in questa sessione **e** nelle sessioni future è ancorata a un contesto concreto, e l'utente non deve correggere output generico.

### Procedura

L'assistant gestisce l'intervista usando `AskUserQuestion` (tool nativo di Claude Code), in **batch di 2–4 domande alla volta** per ridurre la fatica conversazionale. Sequenza tipica:

1. **Pre-scansione del contesto.** Prima di chiedere qualsiasi cosa, l'assistant rilegge il messaggio iniziale dell'utente e il prompt corrente. Spesso il `Register`, il `Site type`, o i `Target users` sono già impliciti. Si annotano come **pre-compilati con bassa confidence** e si chiede solo conferma in fase di intervista, invece di ri-chiederli da zero.

2. **Round 1 — chi e che cosa** (le decisioni più strutturali, hanno cascading effects). Domande: `Register`, `Site type`, `Target users`. Per `Site type` proporre i 5 valori con la one-liner di significato; per `Register` spiegare la differenza brand vs product in mezza riga.

3. **Round 2 — come parla e come appare** (le decisioni di tono). Domande: `Brand personality` (3–5 aggettivi), `Voice / tone` (lingua, persona, tipo di costruzione frase, cosa NON dire).

4. **Round 3 — i principi guida** (le decisioni di pattern). Domande: `Design principles` (3–5 principi), `Anti-references` (cosa NON essere). Per `Asset paths`: chiedere se i path standard (`assets/{ASSETS_VERSION}/icons/logo.svg`, ecc.) sono validi o se vanno modificati; in genere SI conferma e si compila in automatico.

5. **Round 4 — opzionali** (skippabili). Chiedere se ci sono `Constraints` reali (cose che il cliente proibisce, vincoli tecnici, scadenze) e `Inspirations` (2–3 reference link). L'utente può rispondere "skip" su entrambi.

6. **Scrittura del file.** Dopo ogni round, l'assistant aggiorna `PRODUCT.md` a root del sito con le risposte. Non aspetta la fine — incrementale. Così se la sessione si interrompe, le risposte già date sono salvate.

7. **Verifica della soglia 80%.** Dopo Round 3, controlla che tutte le 6 sezioni richieste siano compilate **con risposte specifiche** (no "professionale ma friendly" — quello forza un follow-up). Solo allora si dichiara raggiunta la soglia e si procede con il task originale dell'utente.

### Esempi di follow-up quando la risposta è troppo generica

- **Target users → "utenti del web"** → "Ok, ma chi specificamente? Dimmi un'età approssimativa, una geografia, una situazione concreta in cui questo sito serve. Esempio: *coppie 25–40 anni, in Italia o italiani all'estero, che cercano un fotografo per matrimoni autoriale*."
- **Voice / tone → "professionale ma friendly"** → "Quello è il setting di default di tutti i tool. Concretamente: italiano o inglese? Prima persona singolare, plurale, o terza? Frasi corte o ariose? Inglesismi marketing tipo 'boost' / 'unlock' / 'game-changing' — sì o no? Battute o niente battute?"
- **Design principles → "design minimal e moderno"** → "Anche quello è generico. Concretamente: cosa viene prima nella gerarchia visiva — testo, foto, dato? Mobile-first o desktop-first? Animazioni gratuite sì o no? Bianco generoso o densità informativa?"

### Cosa fare se l'utente vuole skippare l'intervista

L'utente può dire esplicitamente "skippa l'intervista, procedi con quello che ti ho chiesto". In quel caso:

- L'assistant rispetta la richiesta — ma documenta in chat che l'output sarà generico e probabilmente da rivedere.
- Crea un `PRODUCT.md` placeholder con le 6 sezioni richieste segnate come `# TODO` e una sezione `## Site type` riempita con il default ragionevole inferito dal prompt (es. `corporate` se non si capisce; oppure l'unico site_type compatibile con la richiesta).
- Procede con il task ma marca il proprio output come "based on generic Wonder conventions — refine after compiling `PRODUCT.md`".

### Cosa NON fare durante l'intervista

- **Non scrivere il task originale dell'utente** prima di aver raggiunto la soglia 80% (tranne nel caso "skip" sopra). Anche se l'utente lo richiede insistentemente, è meglio una pausa di 10 minuti per allineamento che un output da rifare.
- **Non chiedere tutte le 8–10 sezioni in un unico AskUserQuestion** — il tool ha un limite di 4 domande per call, e cognitivamente è meglio andare in round tematici.
- **Non lasciare risposte generiche passare**. Se l'utente scrive "professionale ma friendly" come voice, fai il follow-up. Una volta sola — se anche la seconda risposta è vaga, accetta e procedi (non vale la pena bloccare per perfezionismo).
- **Non aggiungere domande fuori dallo schema canonico** di `PRODUCT.md`. Se ti viene voglia di chiedere qualcosa che non è una delle 8–10 sezioni documentate, probabilmente è una domanda da fare al momento opportuno nel task vero e proprio, non in fase di setup.

## Template

Copia il template da [`examples.md` §6](examples.md#6-productmd--brand-and-project-context) e compilalo. Tipicamente 10–15 minuti la prima volta, poi si itera mentre il sito cresce.
