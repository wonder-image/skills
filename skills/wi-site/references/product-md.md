# `PRODUCT.md` — brand & project context per un sito Wonder

## Contents

- [Purpose](#purpose)
- [Where it lives](#where-it-lives)
- [Schema](#schema)
- [`site_type` field — semantica](#site_type-field--semantica)
- [DESIGN.md: generato dal framework](#designmd-generato-dal-framework)
- [How wi-site uses it](#how-wi-site-uses-it)
- [Compilation procedure (interview-driven)](#compilation-procedure-interview-driven)
- [Template](#template)

## Purpose

Un sito Wonder è "come" — placement dei file, helper, design system già definiti dal framework. `PRODUCT.md` aggiunge il "per chi e perché": brand voice, target users, principi di design, asset paths, e — soprattutto — la **tipologia di sito** (`site_type`) che modula tutto il resto.

Senza `PRODUCT.md` un assistant può solo seguire le convenzioni Wonder. Con `PRODUCT.md`, l'output diventa coerente con il brand e con la natura del progetto (una landing di conversione vs un sito corporate vs un blog vs un ecom si scrivono e si presentano in modo diverso).

`PRODUCT.md` è la convenzione che `wi-site` legge come primo step.

## Where it lives

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

## Schema

`PRODUCT.md` è un file markdown con sezioni canoniche. Ogni sezione è 1–3 righe — se devi scriverne mezza pagina, probabilmente stai mischiando contenuti che vanno altrove (copy = `lang/{locale}/*.json`; token visivi = `assets/{ASSETS_VERSION}/css/set-up/`).

### Sezioni richieste

- **Register** — una sola parola: `brand` (il design ESPRIME il prodotto, es. studio creativo, agenzia di moda) o `product` (il design SERVE il prodotto, es. SaaS, ecom, tool).
- **Site type** — uno dei 5 valori in [`site_type` field — semantica](#site_type-field--semantica).
- **Target users** — chi userà il sito, in una frase. *"Coppie 25–40 anni che cercano un fotografo per matrimoni in Toscana."*
- **Brand personality** — 3–5 aggettivi. *"Caldo, autoriale, raffinato, mai aulico, mai stocky."*
- **Voice / tone** — come parla il sito. *"Italiano, prima persona singolare, frasi corte, niente claim. Nei legali, terza persona neutra."*
- **Design principles** — 3–5 principi che guidano le scelte UI. *"Foto in primo piano, testo solo quando necessario; lettura mobile-first; niente animazioni gratuite."*

### Sezioni opzionali ma utili

- **Anti-references** — cosa NON essere. *"Niente photography stocky di matrimoni patinati; niente font script; niente musica autoplay."*
- **Asset paths** — OG image, illustrazioni primarie, altri asset di brand. Logo e favicon **non** si dichiarano qui: si caricano dal backend e vivono in `assets/upload/logos/` (`Logo.png`, `Logo-White.png`, `Logo-Black.png`, `Logo-Icon.png`, `App-Icon.png`) e `assets/upload/favicon.ico` (vedi `App\Path`). Elencare solo asset che esistono davvero, con il path relativo al repo del sito. *Esempio:*
  ```
  - OG default: assets/{ASSETS_VERSION}/images/og-default.jpg
  - Illustrazioni hero: assets/{ASSETS_VERSION}/images/hero/
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
- `wi-site` privilegia la **conversione** rispetto alla profondità informativa

### `corporate`

Multi-page site: home, chi-siamo, servizi, contatti, legal. È la forma di default dello scaffold `wonder-image/new-site`. Niente modulo verticale specifico.

- Pagine: `custom/view/pages/frontend/`
- Moduli Composer: nessuno (di solito)
- Backend solo per content backend-editable (es. `app/Models/Service.php` + Resource)
- Default reasonable per la maggior parte dei siti istituzionali

### `blog`

Sito orientato a contenuti editoriali. Abilitare il modulo `wonder-image/blog` in `custom/config/modules.php` *(quando pubblicato — al momento è una convenzione, verifica su packagist prima di prometterlo al cliente)*.

- Pagine post/listing/categoria/tag: vengono dal modulo
- Override: solo `custom/view/components/frontend/...` mirati a riconnotare la presentazione
- Wi-site propone `wonder-image/blog` come prima cosa, non un Model `Post` custom
- `wi-site` privilegia la **leggibilità** (line length, type scale, contrast) rispetto al visual flash
- Finché il modulo non è disponibile, `wi-site` può proporre Model + Resource custom (`app/Models/Post.php` + `app/Resources/PostResource.php`) come fallback temporaneo, segnalando esplicitamente che è un'implementazione di transizione da sostituire quando il modulo arriva

### `ecom`

Catalogo + carrello + checkout. Installare il modulo `wonder-image/ecommerce` e abilitarlo in `custom/config/modules.php` con la chiave `ecommerce`, insieme alle sue dipendenze `customer-account` e `gestionale` (esempio in [`examples.md` § 5](examples.md#5-customconfigmodulesphp--enable-an-external-composer-module)).

- Pagine prodotto/listing/cart/checkout: dal modulo
- Override: solo per riconnotare la presentazione
- Wi-site propone `wonder-image/ecommerce`, non un Model `Product` custom
- `wi-site` privilegia **trust signals**, **friction-checkout** e **gallery quality** rispetto all'editorial

### `rsvp`

Invitati + conferme + gestione lista. Abilitare il modulo `wonder-image/rsvp` (quando disponibile) in `custom/config/modules.php`.

- Pagine form RSVP / dashboard organizzatore: dal modulo
- Override: solo per riconnotare la presentazione e localizzare i form
- Wi-site propone `wonder-image/rsvp`, non un Model `Invitation` custom

### Quando `site_type` non corrisponde

Se il progetto è davvero ibrido (un blog *dentro* un sito corporate, una landing che vende un singolo prodotto), scegli il `site_type` **dominante** — quello che descrive la maggior parte delle pagine e dei flussi. Documenta le eccezioni nella sezione **Constraints** del `PRODUCT.md`.

## DESIGN.md: generato dal framework

`docs/ai/DESIGN.md` esiste, ma non lo scrive nessuno a mano: `wonder-image/app` lo genera dai token di `assets/{ASSETS_VERSION}/css/set-up/color.css` e `root.css` ogni volta che li rigenera in sviluppo (palette, tipografia, spaziatura, bottoni, classi utility). È gitignorato come `root.css` e `color.css`, perché deriva dal DB.

`PRODUCT.md` dice *chi* e *perché*; `DESIGN.md` dice *con quali colori e font*. Le proposte visive delle skill esterne si mappano su quei token o si scartano: la regola di precedenza e il fallback per i siti con il framework non aggiornato stanno in [`external-skills.md`](external-skills.md#precedence), le regole d'uso dei token in [`style-and-lib.md`](style-and-lib.md).

## How wi-site uses it

Il primo step di `wi-site` su qualsiasi task di un sito Wonder è: leggere `docs/ai/PRODUCT.md` (o `PRODUCT.md` a root nei siti non ancora migrati, che il readiness check sposta). Tutto quello che segue — placement, copy, scelte di stile, suggerimenti di moduli — deve essere coerente con quanto dichiarato lì.

Il `site_type` in particolare cambia il workflow:

- Una richiesta "aggiungi una pagina prodotti" su `site_type: blog` → wi-site chiede prima conferma (sembra un'azione fuori scope per un blog).
- Una richiesta "aggiungi un articolo" su `site_type: ecom` senza modulo blog attivo → wi-site suggerisce di abilitare `wonder-image/blog` o di confermare se davvero non serve.
- Una richiesta "aggiungi una pagina /shop" su `site_type: corporate` senza modulo ecommerce → wi-site chiede se è il momento di passare a `site_type: ecom` + modulo `wonder-image/ecommerce`.

Quando `PRODUCT.md` manca, wi-site lavora comunque — segue solo le convenzioni Wonder generiche. Suggerisce di crearlo come primo step su qualsiasi sito nuovo.

## Compilation procedure (interview-driven)

**Hard rule.** Quando un sito Wonder non ha `PRODUCT.md`, o quando una delle sue sezioni richieste è vuota (un titolo seguito solo da righe di hint `>` o da commenti HTML, com'è nello scaffold `new-site`), `wi-site` **non procede con il task dell'utente** finché non ha raggiunto **almeno l'80% di chiarezza sul progetto**. La soglia è operazionalizzata così:

- **Tutte e 6 le sezioni richieste compilate**: `Register`, `Site type`, `Target users`, `Brand personality`, `Voice / tone`, `Design principles`
- **Almeno una delle sezioni raccomandate compilata**: `Anti-references` *oppure* `Asset paths`

Le sezioni opzionali (`Constraints`, `Inspirations`) possono restare vuote — l'utente le aggiungerà quando il progetto le richiede.

### Perché un'intervista e non un template a freddo

Il template di [`examples.md` §6](examples.md#6-docsaiproductmd--brand-and-project-context) ha lo scaffolding ma le sezioni sono astratte. Un utente che lo riempie da zero, da solo, in genere scrive frasi vaghe (*"target: utenti interessati"*, *"voce: professionale ma friendly"*) che non producono direzione concreta. L'intervista forza risposte specifiche perché:

- Le domande sono mirate ed esemplificate
- L'assistant può fare follow-up quando la risposta è generica
- L'assistant può inferire da risposte parziali (es. l'utente dice "un sito per il mio studio di fotografia di matrimoni" → `register: brand`, `site_type: corporate`, `target_users: coppie che cercano un fotografo`, tutto da una frase)

Il costo: 10–15 minuti la prima volta. Il guadagno: ogni decisione successiva in questa sessione **e** nelle sessioni future è ancorata a un contesto concreto, e l'utente non deve correggere output generico.

### Procedura

L'assistant gestisce l'intervista usando `AskUserQuestion` (tool nativo di Claude Code), in **batch di 2–4 domande alla volta** per ridurre la fatica conversazionale. Sequenza tipica:

1. **Pre-scansione del contesto.** Prima di chiedere qualsiasi cosa, l'assistant rilegge il messaggio iniziale dell'utente e il prompt corrente. Spesso il `Register`, il `Site type`, o i `Target users` sono già impliciti. Si annotano come **pre-compilati con bassa confidence** e si chiede solo conferma in fase di intervista, invece di ri-chiederli da zero.

2. **Round 1 — chi e che cosa** (le decisioni più strutturali, hanno cascading effects). Domande: `Register`, `Site type`, `Target users`. Per `Site type` proporre i 5 valori con la one-liner di significato; per `Register` spiegare la differenza brand vs product in mezza riga.

3. **Round 2 — come parla e come appare** (le decisioni di tono). Domande: `Brand personality` (3–5 aggettivi), `Voice / tone` (lingua, persona, tipo di costruzione frase, cosa NON dire).

4. **Round 3 — i principi guida** (le decisioni di pattern). Domande: `Design principles` (3–5 principi), `Anti-references` (cosa NON essere). Per `Asset paths`: logo e favicon vivono già in `assets/upload/logos/` e `assets/upload/favicon.ico` (caricati dal backend), quindi non si chiedono; chiedere solo se esistono altri asset da dichiarare (immagine OG di default, illustrazioni) e con quale path.

5. **Round 4 — opzionali** (skippabili). Chiedere se ci sono `Constraints` reali (cose che il cliente proibisce, vincoli tecnici, scadenze) e `Inspirations` (2–3 reference link). L'utente può rispondere "skip" su entrambi.

6. **Scrittura del file.** Dopo ogni round, l'assistant aggiorna `docs/ai/PRODUCT.md` con le risposte. Non aspetta la fine — incrementale. Così se la sessione si interrompe, le risposte già date sono salvate.

7. **Verifica della soglia 80%.** Dopo Round 3, controlla che tutte le 6 sezioni richieste siano compilate **con risposte specifiche** (no "professionale ma friendly" — quello forza un follow-up). Solo allora si dichiara raggiunta la soglia e si procede con il task originale dell'utente.

### Esempi di follow-up quando la risposta è troppo generica

- **Target users → "utenti del web"** → "Ok, ma chi specificamente? Dimmi un'età approssimativa, una geografia, una situazione concreta in cui questo sito serve. Esempio: *coppie 25–40 anni, in Italia o italiani all'estero, che cercano un fotografo per matrimoni autoriale*."
- **Voice / tone → "professionale ma friendly"** → "Quello è il setting di default di tutti i tool. Concretamente: italiano o inglese? Prima persona singolare, plurale, o terza? Frasi corte o ariose? Inglesismi marketing tipo 'boost' / 'unlock' / 'game-changing' — sì o no? Battute o niente battute?"
- **Design principles → "design minimal e moderno"** → "Anche quello è generico. Concretamente: cosa viene prima nella gerarchia visiva — testo, foto, dato? Mobile-first o desktop-first? Animazioni gratuite sì o no? Bianco generoso o densità informativa?"

### Cosa fare se l'utente vuole skippare l'intervista

L'utente può dire esplicitamente "skippa l'intervista, procedi con quello che ti ho chiesto". In quel caso:

- L'assistant rispetta la richiesta — ma documenta in chat che l'output sarà generico e probabilmente da rivedere.
- Crea `docs/ai/PRODUCT.md` placeholder con le 6 sezioni richieste segnate come `# TODO` e una sezione `## Site type` riempita con il default ragionevole inferito dal prompt (es. `corporate` se non si capisce; oppure l'unico site_type compatibile con la richiesta).
- Procede con il task ma marca il proprio output come "based on generic Wonder conventions — refine after compiling `PRODUCT.md`".

### Cosa NON fare durante l'intervista

- **Non scrivere il task originale dell'utente** prima di aver raggiunto la soglia 80% (tranne nel caso "skip" sopra). Anche se l'utente lo richiede insistentemente, è meglio una pausa di 10 minuti per allineamento che un output da rifare.
- **Non chiedere tutte le 8–10 sezioni in un unico AskUserQuestion** — il tool ha un limite di 4 domande per call, e cognitivamente è meglio andare in round tematici.
- **Non lasciare risposte generiche passare**. Se l'utente scrive "professionale ma friendly" come voice, fai il follow-up. Una volta sola — se anche la seconda risposta è vaga, accetta e procedi (non vale la pena bloccare per perfezionismo).
- **Non aggiungere domande fuori dallo schema canonico** di `PRODUCT.md`. Se ti viene voglia di chiedere qualcosa che non è una delle 8–10 sezioni documentate, probabilmente è una domanda da fare al momento opportuno nel task vero e proprio, non in fase di setup.

## Template

Copia il template da [`examples.md` §6](examples.md#6-docsaiproductmd--brand-and-project-context) in `docs/ai/PRODUCT.md` e compilalo. Tipicamente 10–15 minuti la prima volta, poi si itera mentre il sito cresce.
