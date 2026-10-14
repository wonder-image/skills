# `wonder-image/app`: generazione di `docs/ai/DESIGN.md` — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ogni volta che il framework rigenera `root.css` in ambiente di sviluppo, scrive anche `docs/ai/DESIGN.md`, una proiezione Markdown dei token (palette, tipografia, spaziatura, bottoni, classi utility) che gli agenti AI leggono al posto del CSS.

**Architecture:** Un metodo puro `CssTokens::designMd()` accanto a `root()` e `colorClasses()` produce il Markdown dagli stessi input di `root()`. Un helper globale `cssDesignMd()` in `app/function/other/css.php` scrive il file ed è chiamato da `cssRoot()` dopo `root.css`, solo quando `\Wonder\App\Debug::enabled()` è vero; un errore di scrittura va nel `Logger` senza bloccare il salvataggio del CSS. Nessun altro punto del framework cambia: tutti i trigger (backend, `forge import`, `forge update`) passano già da `cssRoot()`.

**Tech Stack:** PHP 8 (`wonder-image/app`), test standalone con `tests/harness.php` (`check()` / `summary()`), Composer autoload.

**Spec:** `docs/superpowers/specs/2026-10-14-external-skills-routing-design.md` (repo `wonder-image/skills`), sezione 4. Il piano è il primo dei tre previsti dalla sezione 7: va eseguito e mergiato prima di `2026-10-14-new-site-docs-ai.md`.

## Global Constraints

- **Repo e cwd:** tutti i comandi di questo piano si eseguono in `/Users/andreamarinoni/Desktop/PROGETTI/template/app` (checkout di `wonder-image/app`, branch `main`, HEAD `6aa44f5c`). Il piano vive nel repo `skills`, il codice no: non lanciare `php tests/...` dalla cartella del piano.
- **Branch:** `design-md-generator` creato da `main`. Una PR su `main` a fine piano.
- **Firma:** `public static function designMd(object $default, array $colors, array $fontFamilies): string` in `class/View/CssTokens.php` (spec 4.1). `$default` è la riga `css_default` (già fusa con `SeedDefaults::cssDefaultRow()`), `$colors` le righe `css_color` (array o oggetti con `var`, `color`, `contrast`), `$fontFamilies` le famiglie già decodificate con chiavi `default`, `title_big`, `title`, `subtitle`, `text`, `text_small`.
- **Schema del file:** quello di spec 4.2, con le variabili tipografiche scritte per esteso (`--title-font-family`, `--title-font-weight`, `--title-font-size`, `--title-line-height`, ecc.). Il commento di testa inizia con `<!-- Generato da wonder-image/app (CssTokens::designMd)`.
- **Niente segnaposto nel file reale:** il Markdown generato non deve contenere il carattere `…` (U+2026) né la sequenza `{$`. I puntini della spec sono illustrativi. Le sequenze letterali `{ASSETS_VERSION}`, `{0..100}` e `{r}` sono volute.
- **Gate:** la scrittura su disco avviene solo con `$update === true` e `\Wonder\App\Debug::enabled() === true` (spec D3). `cssRoot(false)` resta invariato e ritorna la stringa CSS.
- **Logger:** `Logger::log()` con `$renderDebug = true` e debug attivo stampa l'errore e fa `exit()` (`class/App/Logger.php:63-66`). L'hook gira solo in debug, quindi deve passare `false` come ultimo argomento: un backend che salva un colore non deve mai morire per una cartella non scrivibile.
- **Stile:** docblock e nomi dei test in italiano, come il resto del file. `$RETURN .=` per costruire la stringa, come `root()`. Nessuna dipendenza nuova.
- **Commit:** ogni messaggio termina con la riga `Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>`.
- `.gitignore` del framework ha `/tests/*` con eccezioni esplicite: `tests/View/CssTokensTest.php` è già tracciato (`git ls-files` lo elenca), quindi `git add` normale funziona. Se un file nuovo sotto `tests/` non venisse preso, usare `git add -f`.

## Review Focus

1. **Palette vuota** (`$colors = []`, sito appena installato senza righe `css_color`): il file deve restare Markdown valido, con la tabella palette senza righe e la sezione classi utility che dice che non ci sono ruoli, senza eccezioni. Test in Task 1.
2. **Riga colore senza `contrast` o senza `var`** (righe incomplete importate da un JSON): la riga senza `var` si salta, quella senza `contrast` ha la cella vuota; nessun warning PHP. Test in Task 1.
3. **Contrasto non esadecimale** (`var(--light-color)` per `dark` e `light` nella palette di default): va stampato così com'è, come fa `root()`; un'asserzione "ogni contrasto è `#rrggbb`" fallirebbe sulla palette di default. Test in Task 1.
4. **Gruppo tipografico senza famiglia** (`$fontFamilies` senza `subtitle`): eredita la famiglia `default`, come in `root()`. Test in Task 1.
5. **Cartella `docs/ai` non scrivibile o `$ROOT` di sola lettura:** il salvataggio del CSS deve completarsi e il problema finire in `storage/logs/error.log`, senza output né `exit()`. Non testabile a unità (funzione legacy globale con `info()`/`sqlSelect()`): verifica manuale in Task 2, passo 6.

---

### Task 1: `CssTokens::designMd()` con i test

**Files:**
- Modify: `class/View/CssTokens.php` (aggiungere il metodo dopo `colorClasses()`, prima di `defaultRoot()`, circa riga 272)
- Test: `tests/View/CssTokensTest.php` (aggiungere i casi prima di `summary();`, riga 93)

**Interfaces:**
- Consumes: `SeedDefaults::cssDefaultRow(): array`, `RuntimeDefaults::defaultColors(): array` (righe con `var`, `name`, `color`, `contrast`), `CssFontFamily::fallback(): string` = `'"Roboto", sans-serif'`.
- Produces: `CssTokens::designMd(object $default, array $colors, array $fontFamilies): string`, usato da Task 2.

- [ ] **Step 1: Creare il branch**

```bash
cd /Users/andreamarinoni/Desktop/PROGETTI/template/app && git checkout main && git status --short && git checkout -b design-md-generator
```

Expected: `git status --short` vuoto (checkout pulito), branch `design-md-generator` creato.

- [ ] **Step 2: Scrivere i test che falliscono**

Aprire `tests/View/CssTokensTest.php` e inserire questo blocco **subito prima** della riga `summary();` (ultima riga del file):

```php
echo "\nCssTokens::designMd\n";

$designFonts = [
    'default' => '"Roboto", sans-serif',
    'title_big' => '"Playfair Display", serif',
    'title' => '"Lora", serif',
    'subtitle' => '"Inter", sans-serif',
    'text' => '"Source Sans 3", sans-serif',
    'text_small' => '"Fira Sans", sans-serif',
];

$md = CssTokens::designMd(
    (object) \Wonder\App\SeedDefaults::cssDefaultRow(),
    \Wonder\App\RuntimeDefaults::defaultColors(),
    $designFonts
);

check('designMd() apre con il commento di generazione e il titolo', function () use ($md) {
    return str_starts_with($md, '<!-- Generato da wonder-image/app (CssTokens::designMd)')
        && str_contains($md, "\n# Design tokens\n")
        && str_contains($md, 'Proiezione leggibile di `assets/{ASSETS_VERSION}/css/set-up/root.css` e `color.css`.');
});

check('designMd() ha una riga di palette per ogni colore, con il contrasto anche quando non è esadecimale', function () use ($md) {
    return str_contains($md, "| Ruolo | Colore | Contrasto (`-o`) | Variabili |\n|---|---|---|---|\n| primary | #000000 | #ffffff | `--primary-color`, `--primary-o-color`, `--primary-color-{0..100}`, `--primary-o-color-{0..100}` |\n")
        && str_contains($md, '| dark | #343a40 | var(--light-color) | `--dark-color`, `--dark-o-color`, `--dark-color-{0..100}`, `--dark-o-color-{0..100}` |')
        && str_contains($md, '| white | #ffffff | #000000 |')
        && substr_count($md, '-o-color-{0..100}` |') === 10
        && str_contains($md, 'Testo di default: `--tx-color` #000000 (`--tx-color-{0..100}`) · Sfondo di default: `--bg-color` #ffffff (`--bg-color-{0..100}`)');
});

check('designMd() scrive le sei righe tipografiche con la famiglia di ciascun gruppo', function () use ($md) {
    return str_contains($md, '| default | "Roboto", sans-serif | 400 | 16px | 16px | `--font-family`, `--font-weight`, `--font-size`, `--line-height` |')
        && str_contains($md, '| title-big | "Playfair Display", serif | 500 | 40px | 40px | `--title-big-font-family`, `--title-big-font-weight`, `--title-big-font-size`, `--title-big-line-height` |')
        && str_contains($md, '| title | "Lora", serif | 500 | 32px | 32px | `--title-font-family`, `--title-font-weight`, `--title-font-size`, `--title-line-height` |')
        && str_contains($md, '| subtitle | "Inter", sans-serif | 400 | 24px | 24px | `--subtitle-font-family`, `--subtitle-font-weight`, `--subtitle-font-size`, `--subtitle-line-height` |')
        && str_contains($md, '| text | "Source Sans 3", sans-serif | 300 | 16px | 20px | `--text-font-family`, `--text-font-weight`, `--text-font-size`, `--text-line-height` |')
        && str_contains($md, '| text-small | "Fira Sans", sans-serif | 300 | 12px | 12px | `--text-small-font-family`, `--text-small-font-weight`, `--text-small-font-size`, `--text-small-line-height` |');
});

check('un gruppo senza famiglia eredita quella di default, come in root()', function () {
    $md = CssTokens::designMd(
        (object) \Wonder\App\SeedDefaults::cssDefaultRow(),
        [],
        ['default' => '"Inter", sans-serif', 'title' => '"Lora", serif']
    );

    return str_contains($md, '| title | "Lora", serif | 500 |')
        && str_contains($md, '| subtitle | "Inter", sans-serif | 400 |')
        && str_contains($md, '| text-small | "Inter", sans-serif | 300 |');
});

check('designMd() riporta spaziatura, bottoni e badge dal seed', function () use ($md) {
    return str_contains($md, "## Spaziatura e layout\n\n- `--spacer`: 4px\n- `--header-height`: 80px\n")
        && str_contains($md, "| Componente | Font size | Line height | Peso | Radius | Bordo |\n|---|---|---|---|---|---|\n| button | 14px | 16px | 400 | 5px | 2px |\n| badge | 12px | 12px | 400 | 5px | 1px |\n");
});

check('le classi utility elencano i ruoli della palette e le varianti hover', function () use ($md) {
    return str_contains($md, 'Per ogni ruolo `{r}` della palette (primary, secondary, success, info, danger, dark, light, gray, black, white):')
        && str_contains($md, '`.tx-{r}` `.tx-{r}-o` `.bg-{r}` `.bg-{r}-o` `.bg-{r}-{0..100 passo 10}` `.bg-{r}-o-{N}` `.tx-stroke-{r}`')
        && str_contains($md, '`.btn-{r}` `.btn-{r}-o` `.badge-{r}` `.badge-{r}-o` e le varianti `hover\:` di `tx-`/`bg-`.')
        && str_contains($md, "## Come usarlo\n\n- Un colore o un font proposto da una skill esterna si mappa su un ruolo o un gruppo qui sopra, oppure si scarta.\n- Voce, tono e personalità del brand non stanno qui: vedi `docs/ai/PRODUCT.md`.\n");
});

check('nessun segnaposto rimasto nel file generato', function () use ($md) {
    return !str_contains($md, '…') && !str_contains($md, '{$');
});

check('una palette vuota produce un file valido con la tabella senza righe', function () {
    $md = CssTokens::designMd((object) \Wonder\App\SeedDefaults::cssDefaultRow(), [], []);

    return str_contains($md, "| Ruolo | Colore | Contrasto (`-o`) | Variabili |\n|---|---|---|---|\n\nTesto di default:")
        && str_contains($md, 'Per ogni ruolo `{r}` della palette (nessun ruolo in css_color):')
        && str_contains($md, '| default |  | 400 | 16px | 16px |');
});

check('una riga colore senza contrasto ha la cella vuota e una senza var si salta, senza warning', function () {
    set_error_handler(static function (int $no, string $message): bool {
        throw new \ErrorException($message, 0, $no);
    });

    try {
        $md = CssTokens::designMd(
            (object) \Wonder\App\SeedDefaults::cssDefaultRow(),
            [
                ['var' => 'brand', 'color' => '#123456'],
                ['color' => '#000000', 'contrast' => '#ffffff'],
                (object) ['var' => 'accent', 'color' => '#abcdef', 'contrast' => '#000000'],
            ],
            []
        );
    } finally {
        restore_error_handler();
    }

    return str_contains($md, '| brand | #123456 |  | `--brand-color`, `--brand-o-color`, `--brand-color-{0..100}`, `--brand-o-color-{0..100}` |')
        && str_contains($md, '| accent | #abcdef | #000000 |')
        && substr_count($md, '-o-color-{0..100}` |') === 2
        && str_contains($md, 'della palette (brand, accent):');
});
```

- [ ] **Step 3: Eseguire il test e verificare che fallisca**

Run: `php tests/View/CssTokensTest.php`

Expected: i 5 test esistenti passano, poi la riga `CssTokens::designMd` e un errore fatale `Call to undefined method Wonder\View\CssTokens::designMd()` (la chiamata è fuori da `check()`, quindi PHP si ferma lì). Va bene: il metodo non esiste ancora.

- [ ] **Step 4: Implementare `designMd()`**

In `class/View/CssTokens.php`, inserire il metodo tra la fine di `colorClasses()` (la riga `    }` dopo `return $RETURN;`, circa riga 272) e il docblock di `defaultRoot()`:

```php
    /**
     * `docs/ai/DESIGN.md`: la proiezione leggibile dei token per gli agenti AI
     * (palette, tipografia, spaziatura, bottoni, classi utility). Stessi input
     * di `root()` per la parte che riguarda il brand; le righe `css_input`,
     * `css_auth`, `css_modal`, `css_dropdown` e `css_alert` descrivono il
     * backend e restano fuori. La scrittura su disco sta in `cssDesignMd()`.
     *
     * @param array<int, array<string, mixed>|object> $colors righe `css_color` (`var`, `color`, `contrast`)
     * @param array<string, string> $fontFamilies chiavi `default`, `title_big`, `title`, `subtitle`, `text`, `text_small`
     */
    public static function designMd(object $default, array $colors, array $fontFamilies): string
    {
        $fontFamilyDefault = (string) ($fontFamilies['default'] ?? '');
        $families = [
            'default' => $fontFamilyDefault,
            'title_big' => (string) ($fontFamilies['title_big'] ?? $fontFamilyDefault),
            'title' => (string) ($fontFamilies['title'] ?? $fontFamilyDefault),
            'subtitle' => (string) ($fontFamilies['subtitle'] ?? $fontFamilyDefault),
            'text' => (string) ($fontFamilies['text'] ?? $fontFamilyDefault),
            'text_small' => (string) ($fontFamilies['text_small'] ?? $fontFamilyDefault),
        ];

        $value = static function (string $property) use ($default): string {
            return (string) ($default->{$property} ?? '');
        };

        $RETURN = "<!-- Generato da wonder-image/app (CssTokens::designMd) dalle tabelle css_default, css_font, css_color.\n";
        $RETURN .= "     Non modificare a mano: cambia i valori nel backend (CSS → Colori / Font / Impostazioni) o nel seed,\n";
        $RETURN .= "     poi `php forge update --local`. Il file si rigenera a ogni salvataggio. -->\n";
        $RETURN .= "\n";
        $RETURN .= "# Design tokens\n";
        $RETURN .= "\n";
        $RETURN .= "Proiezione leggibile di `assets/{ASSETS_VERSION}/css/set-up/root.css` e `color.css`.\n";
        $RETURN .= "Le variabili e le classi elencate sono le uniche fonti di colore e tipografia ammesse nel sito.\n";
        $RETURN .= "\n";

        $RETURN .= "## Palette\n";
        $RETURN .= "\n";
        $RETURN .= "| Ruolo | Colore | Contrasto (`-o`) | Variabili |\n";
        $RETURN .= "|---|---|---|---|\n";

        $roles = [];

        foreach ($colors as $row) {
            $row = (array) $row;
            $var = (string) ($row['var'] ?? '');

            if ($var === '') {
                continue;
            }

            $roles[] = $var;
            $colorHEX = (string) ($row['color'] ?? '');
            $contrastHEX = (string) ($row['contrast'] ?? '');

            $RETURN .= "| $var | $colorHEX | $contrastHEX | `--$var-color`, `--$var-o-color`, `--$var-color-{0..100}`, `--$var-o-color-{0..100}` |\n";
        }

        $RETURN .= "\n";
        $RETURN .= "Testo di default: `--tx-color` {$value('tx_color')} (`--tx-color-{0..100}`) · Sfondo di default: `--bg-color` {$value('bg_color')} (`--bg-color-{0..100}`)\n";
        $RETURN .= "\n";

        $RETURN .= "## Tipografia\n";
        $RETURN .= "\n";
        $RETURN .= "| Gruppo | Famiglia | Peso | Dimensione | Interlinea | Variabili |\n";
        $RETURN .= "|---|---|---|---|---|---|\n";

        // [etichetta, chiave famiglia, prefisso variabile CSS, prefisso colonna css_default]
        $groups = [
            ['default', 'default', '', ''],
            ['title-big', 'title_big', 'title-big-', 'title_big_'],
            ['title', 'title', 'title-', 'title_'],
            ['subtitle', 'subtitle', 'subtitle-', 'subtitle_'],
            ['text', 'text', 'text-', 'text_'],
            ['text-small', 'text_small', 'text-small-', 'text_small_'],
        ];

        foreach ($groups as [$label, $familyKey, $cssPrefix, $rowPrefix]) {
            $weight = $value($rowPrefix.'font_weight');
            $size = $value($rowPrefix.'font_size');
            $lineHeight = $value($rowPrefix.'line_height');

            $RETURN .= "| $label | {$families[$familyKey]} | $weight | {$size}px | {$lineHeight}px | `--{$cssPrefix}font-family`, `--{$cssPrefix}font-weight`, `--{$cssPrefix}font-size`, `--{$cssPrefix}line-height` |\n";
        }

        $RETURN .= "\n";
        $RETURN .= "## Spaziatura e layout\n";
        $RETURN .= "\n";
        $RETURN .= "- `--spacer`: {$value('spacer')}px\n";
        $RETURN .= "- `--header-height`: {$value('header_height')}px\n";
        $RETURN .= "\n";

        $RETURN .= "## Bottoni e badge\n";
        $RETURN .= "\n";
        $RETURN .= "| Componente | Font size | Line height | Peso | Radius | Bordo |\n";
        $RETURN .= "|---|---|---|---|---|---|\n";
        $RETURN .= "| button | {$value('button_font_size')}px | {$value('button_line_height')}px | {$value('button_font_weight')} | {$value('button_border_radius')}px | {$value('button_border_width')}px |\n";
        $RETURN .= "| badge | {$value('badge_font_size')}px | {$value('badge_line_height')}px | {$value('badge_font_weight')} | {$value('badge_border_radius')}px | {$value('badge_border_width')}px |\n";
        $RETURN .= "\n";

        $roleList = $roles === [] ? 'nessun ruolo in css_color' : implode(', ', $roles);

        $RETURN .= "## Classi utility (da color.css)\n";
        $RETURN .= "\n";
        $RETURN .= "Per ogni ruolo `{r}` della palette ($roleList):\n";
        $RETURN .= '`.tx-{r}` `.tx-{r}-o` `.bg-{r}` `.bg-{r}-o` `.bg-{r}-{0..100 passo 10}` `.bg-{r}-o-{N}` `.tx-stroke-{r}`'."\n";
        $RETURN .= '`.btn-{r}` `.btn-{r}-o` `.badge-{r}` `.badge-{r}-o` e le varianti `hover\:` di `tx-`/`bg-`.'."\n";
        $RETURN .= "\n";

        $RETURN .= "## Come usarlo\n";
        $RETURN .= "\n";
        $RETURN .= "- Un colore o un font proposto da una skill esterna si mappa su un ruolo o un gruppo qui sopra, oppure si scarta.\n";
        $RETURN .= "- Voce, tono e personalità del brand non stanno qui: vedi `docs/ai/PRODUCT.md`.\n";

        return $RETURN;
    }
```

Nota per chi implementa: in PHP `"{$value('tx_color')}"` chiama la closure dentro la stringa; `"`--$var-color`"` interpola `$var` e lascia `-color` come testo. Le due righe delle classi utility sono in apici singoli per non far interpretare `\:` e `{`.

- [ ] **Step 5: Eseguire il test e verificare che passi**

Run: `php -l class/View/CssTokens.php && php tests/View/CssTokensTest.php`

Expected: `No syntax error`, poi tutte le righe con `✓` e in coda `14 test, 0 falliti` (5 esistenti + 9 nuovi), exit code 0.

- [ ] **Step 6: Controllare a occhio il Markdown generato**

Run:

```bash
php -r 'require "vendor/autoload.php"; echo \Wonder\View\CssTokens::designMd((object) \Wonder\App\SeedDefaults::cssDefaultRow(), \Wonder\App\RuntimeDefaults::defaultColors(), ["default" => \Wonder\App\Support\CssFontFamily::fallback()]);'
```

Expected: il documento di spec 4.2 con i valori veri: 10 righe di palette, 6 righe tipografiche tutte con `"Roboto", sans-serif`, `--spacer`: 4px, `--header-height`: 80px. Nessun `…`.

- [ ] **Step 7: Commit**

```bash
git add class/View/CssTokens.php tests/View/CssTokensTest.php
git commit -m "$(cat <<'MSG'
CssTokens::designMd(): proiezione Markdown dei token per docs/ai/DESIGN.md

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

---

### Task 2: hook di scrittura in `cssRoot()`

**Files:**
- Modify: `app/function/other/css.php:3-60` (`cssRoot()`), più un nuovo helper `cssDesignMd()` in coda al file

**Interfaces:**
- Consumes: `CssTokens::designMd(object, array, array): string` (Task 1); `\Wonder\App\Debug::enabled(): bool`; `\Wonder\App\Logger::log(Throwable $exception, string $service, string $action, string $level = 'ERROR', string $file = 'error', array $context = [], bool $renderDebug = true): void`.
- Produces: `cssDesignMd(string $root, object $default, array $colors, array $fontFamilies): void` (funzione globale, stesso file di `cssRoot()`/`cssColor()`); il file `$ROOT/docs/ai/DESIGN.md`.

- [ ] **Step 1: Estrarre colori e famiglie in variabili e aggiungere l'hook**

Sostituire l'intera funzione `cssRoot()` (dalla riga `    function cssRoot($update = true) {` fino alla sua `    }` di chiusura, righe 3-60) con:

```php
    function cssRoot($update = true) {

        global $DEFAULT;
        global $ROOT;

        $PATH = $ROOT.'/assets/'.$_ENV['ASSETS_VERSION'].'/css/set-up/root.css';

        $CSS_DEFAULT = \Wonder\App\SeedDefaults::mergeRowDefaults(info('css_default', 'id', '1'), \Wonder\App\SeedDefaults::cssDefaultRow());
        $CSS_INPUT = \Wonder\App\SeedDefaults::mergeRowDefaults(info('css_input', 'id', '1'), \Wonder\App\SeedDefaults::cssInputRow());
        $CSS_AUTH = \Wonder\App\SeedDefaults::mergeRowDefaults(info('css_auth', 'id', '1'), \Wonder\App\SeedDefaults::cssAuthRow());

        $CSS_MODAL = \Wonder\App\SeedDefaults::mergeRowDefaults(info('css_modal', 'id', '1'), \Wonder\App\SeedDefaults::cssModalRow());
        $CSS_DROPDOWN = \Wonder\App\SeedDefaults::mergeRowDefaults(info('css_dropdown', 'id', '1'), \Wonder\App\SeedDefaults::cssDropdownRow());
        $CSS_ALERT = \Wonder\App\SeedDefaults::mergeRowDefaults(info('css_alert', 'id', '1'), \Wonder\App\SeedDefaults::cssAlertRow());

        $decodeFontFamily = static function ($fontId): string {
            $fontFamily = (string) (info('css_font', 'id', $fontId)->font_family ?? '');

            return \Wonder\App\Support\CssFontFamily::normalize(
                $fontFamily,
                \Wonder\App\Support\CssFontFamily::fallback()
            );
        };

        // Colori e famiglie servono sia a root.css sia a docs/ai/DESIGN.md.
        $colors = sqlSelect('css_color')->row;
        $fontFamilies = [
            'default' => $decodeFontFamily($CSS_DEFAULT->font_id),
            'title_big' => $decodeFontFamily($CSS_DEFAULT->title_big_font_id),
            'title' => $decodeFontFamily($CSS_DEFAULT->title_font_id),
            'subtitle' => $decodeFontFamily($CSS_DEFAULT->subtitle_font_id),
            'text' => $decodeFontFamily($CSS_DEFAULT->text_font_id),
            'text_small' => $decodeFontFamily($CSS_DEFAULT->text_small_font_id),
        ];

        // Il template delle variabili sta in CssTokens: lo stesso usato dal
        // catalogo dei componenti per i token di default.
        $RETURN = \Wonder\View\CssTokens::root(
            $CSS_DEFAULT,
            $CSS_INPUT,
            $CSS_AUTH,
            $CSS_MODAL,
            $CSS_DROPDOWN,
            $CSS_ALERT,
            $colors,
            $fontFamilies,
            (string) ($DEFAULT->image ?? '')
        );

        if ($update) {

            $FILE = fopen($PATH, "w");
            fwrite($FILE, $RETURN);
            fclose($FILE);

            // Solo in sviluppo: docs/ai/DESIGN.md è la proiezione dei token per
            // gli agenti AI. Deriva dal DB come root.css ed è gitignorato nel
            // sito; in produzione (APP_DEBUG=false) non viene mai scritto.
            if (\Wonder\App\Debug::enabled()) {
                cssDesignMd($ROOT, $CSS_DEFAULT, $colors, $fontFamilies);
            }

        } else {

            return $RETURN;

        }

    }
```

- [ ] **Step 2: Aggiungere l'helper `cssDesignMd()` in coda al file**

Dopo la chiusura di `cssColor()` (ultima `    }` del file, riga 82 prima della modifica), aggiungere:

```php

    /**
     * Scrive docs/ai/DESIGN.md, la proiezione Markdown dei token per gli agenti AI.
     * Chiamata da cssRoot() quando Debug::enabled() è vero. Un errore di scrittura
     * (cartella non creabile, file non scrivibile) finisce nel log e non blocca il
     * salvataggio del CSS: renderDebug è false perché in debug Logger::log()
     * stamperebbe l'errore e farebbe exit() nel mezzo di un salvataggio dal backend.
     */
    function cssDesignMd(string $root, object $default, array $colors, array $fontFamilies): void {

        $DIR = $root.'/docs/ai';
        $PATH = $DIR.'/DESIGN.md';

        try {

            if (!is_dir($DIR) && !@mkdir($DIR, 0777, true) && !is_dir($DIR)) {
                throw new \RuntimeException('Cartella non creabile: '.$DIR);
            }

            $markdown = \Wonder\View\CssTokens::designMd($default, $colors, $fontFamilies);

            if (@file_put_contents($PATH, $markdown) === false) {
                throw new \RuntimeException('File non scrivibile: '.$PATH);
            }

        } catch (\Throwable $exception) {

            \Wonder\App\Logger::log($exception, 'css', 'design-md', 'WARNING', 'error', ['path' => $PATH], false);

        }

    }
```

- [ ] **Step 3: Lint e test di regressione**

Run: `php -l app/function/other/css.php && php tests/View/CssTokensTest.php`

Expected: `No syntax error`, `14 test, 0 falliti`.

- [ ] **Step 4: Verifica manuale in `new-site` (file generato)**

`new-site` (`/Users/andreamarinoni/Developer/boilerplates/new-site`) richiede `wonder-image/app: dev-main` e ha modifiche non committate che non sono nostre (`package.json`, `package-lock.json`, `shared/sync-data.json`): non toccare `composer.json` né il lock. Si punta temporaneamente `vendor/wonder-image/app` al checkout del framework con un symlink.

```bash
cd /Users/andreamarinoni/Developer/boilerplates/new-site
mv vendor/wonder-image/app vendor/wonder-image/app.bak
ln -s /Users/andreamarinoni/Desktop/PROGETTI/template/app vendor/wonder-image/app
grep -n '^APP_DEBUG' .env
rm -f docs/ai/DESIGN.md
php forge update --local
ls -la docs/ai/DESIGN.md && head -20 docs/ai/DESIGN.md
```

Expected: `.env` ha `APP_DEBUG=true` (altrimenti impostarlo per questo passo e ripristinarlo alla fine); `php forge update --local` termina come prima; `docs/ai/DESIGN.md` esiste e la riga `| primary |` ha lo stesso hex di `--primary-color` in `assets/0.0/css/set-up/root.css`; le sei famiglie coincidono con `--font-family`, `--title-big-font-family`, ecc. Confronto rapido:

```bash
grep -n -- '--primary-color:\|--font-family:\|--title-big-font-family:' assets/0.0/css/set-up/root.css
grep -n '| primary |\|| default |\|| title-big |' docs/ai/DESIGN.md
```

Se non esiste un DB locale per `new-site`, questo passo e il successivo si saltano e lo si scrive nella PR.

- [ ] **Step 5: Verifica manuale del gate `APP_DEBUG=false`**

Sempre in `new-site` con il symlink attivo:

```bash
cp .env .env.plan-backup
sed -i '' 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
rm -f docs/ai/DESIGN.md
php forge update --local
ls docs/ai/DESIGN.md 2>&1
mv .env.plan-backup .env
```

Expected: `ls` risponde `No such file or directory` (da CLI `REMOTE_ADDR` e `SERVER_NAME` non ci sono, quindi il fallback localhost di `Debug::enabled()` non scatta). Il `.env` originale è ripristinato.

- [ ] **Step 6: Verifica manuale dell'errore di scrittura (Review Focus 5)**

```bash
rm -rf docs/ai
mkdir -p docs/ai && chmod 500 docs/ai
php forge update --local; echo "exit: $?"
tail -1 storage/logs/error.log
chmod 755 docs/ai
```

Expected: `php forge update --local` termina con exit 0 e senza stampare l'errore; l'ultima riga di `storage/logs/error.log` è un JSON con `"service":"css"`, `"action":"design-md"`, `"level":"WARNING"` e `"message":"File non scrivibile: .../docs/ai/DESIGN.md"`.

- [ ] **Step 7: Ripristinare `new-site`**

```bash
cd /Users/andreamarinoni/Developer/boilerplates/new-site
rm vendor/wonder-image/app && mv vendor/wonder-image/app.bak vendor/wonder-image/app
rm -rf docs
git status --short
```

Expected: `git status --short` mostra solo le tre modifiche preesistenti (`package-lock.json`, `package.json`, `shared/sync-data.json`), nessun `docs/`.

- [ ] **Step 8: Commit**

```bash
cd /Users/andreamarinoni/Desktop/PROGETTI/template/app
git add app/function/other/css.php
git commit -m "$(cat <<'MSG'
cssRoot(): in sviluppo scrive anche docs/ai/DESIGN.md

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
```

---

### Task 3: CHANGELOG e documentazione

**Files:**
- Modify: `CHANGELOG.md:5-6` (sotto `## Unreleased` / `### Added`, come prima voce)
- Modify: `docs/app/piattaforma/asset-e-cache.md:93-103` (sezione "root.css / color.css e il deploy")
- Modify: `docs/app/piattaforma/multi-ambiente.md:184` e `:446` (paragrafo di `forge import` e tabella "Riepilogo")

- [ ] **Step 1: CHANGELOG**

In `CHANGELOG.md`, subito dopo la riga `### Added` (riga 5) e prima della voce `- \`Dropdown\`: ...`, inserire:

```markdown
- `docs/ai/DESIGN.md`: ogni rigenerazione di `root.css` (salvataggio di colori, font e
  impostazioni CSS dal backend, `forge import`, `forge update`) scrive anche
  `docs/ai/DESIGN.md`, la proiezione in Markdown di palette, tipografia, spaziatura,
  bottoni e classi utility pensata per gli agenti AI (`CssTokens::designMd()`). Solo
  con `Debug::enabled()` (`APP_DEBUG=true` o host locale): in produzione il file non
  viene mai scritto. È generato, va gitignorato nel sito e non si modifica a mano; un
  errore di scrittura finisce in `storage/logs/error.log` senza bloccare il CSS.
```

- [ ] **Step 2: `asset-e-cache.md`**

Nella sezione `## root.css / color.css e il deploy`, dopo la lista numerata che termina con la riga `   \`?v=\` da invalidare per questi due file).` (riga 103), aggiungere un paragrafo:

```markdown

In sviluppo (`APP_DEBUG=true` o host locale) la stessa rigenerazione scrive anche
`docs/ai/DESIGN.md`, la proiezione in Markdown dei token per gli agenti AI
(`CssTokens::designMd()`): è gitignorato nel sito come `root.css`/`color.css` e non
si modifica a mano, perché il salvataggio successivo lo sovrascrive. In produzione
il file non viene scritto.
```

- [ ] **Step 3: `multi-ambiente.md`**

Riga 184, sostituire:

```markdown
Importa un file JSON nelle tabelle del DB. Se contiene tabelle CSS, rigenera anche `root.css` e `color.css`:
```

con:

```markdown
Importa un file JSON nelle tabelle del DB. Se contiene tabelle CSS, rigenera anche `root.css` e `color.css` (e, in sviluppo, `docs/ai/DESIGN.md`):
```

Nella tabella `## Riepilogo: cosa e generato, cosa e committato`, dopo la riga

```markdown
| `root.css`, `color.css` | No | `forge update` (da DB <- `sync-data.json`) | DB `css_*` |
```

aggiungere:

```markdown
| `docs/ai/DESIGN.md` | No | `forge update` / salvataggio CSS dal backend, solo con `APP_DEBUG=true` o host locale | DB `css_*` (proiezione di `root.css`/`color.css`) |
```

- [ ] **Step 4: Verifica**

Run:

```bash
grep -n "DESIGN.md" CHANGELOG.md docs/app/piattaforma/asset-e-cache.md docs/app/piattaforma/multi-ambiente.md
```

Expected: almeno una riga per ciascuno dei tre file (CHANGELOG 1, asset-e-cache 1, multi-ambiente 2).

- [ ] **Step 5: Commit e PR**

```bash
git add CHANGELOG.md docs/app/piattaforma/asset-e-cache.md docs/app/piattaforma/multi-ambiente.md
git commit -m "$(cat <<'MSG'
docs: DESIGN.md generato in sviluppo nel changelog e nelle guide

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
MSG
)"
git push -u origin design-md-generator
gh pr create --title "docs/ai/DESIGN.md generato dai token CSS in sviluppo" --body "$(cat <<'MSG'
## Summary
- `CssTokens::designMd()`: proiezione Markdown di palette, tipografia, spaziatura, bottoni e classi utility (stessi input di `root()`), con test in `tests/View/CssTokensTest.php`.
- `cssRoot()`: dopo `root.css`, con `Debug::enabled()` vero, scrive `docs/ai/DESIGN.md` tramite `cssDesignMd()`; errori di scrittura nel log, mai bloccanti.
- CHANGELOG e guide `asset-e-cache.md` / `multi-ambiente.md`.

Spec: `wonder-image/skills` → `docs/superpowers/specs/2026-10-14-external-skills-routing-design.md`, sezione 4. Primo dei tre PR (app → new-site → skills).

## Test plan
- [ ] `php tests/View/CssTokensTest.php` → `14 test, 0 falliti`
- [ ] in `new-site` con il framework di questo branch: `php forge update --local` produce `docs/ai/DESIGN.md` coerente con `root.css`
- [ ] con `APP_DEBUG=false` il file non viene scritto
- [ ] con `docs/ai` non scrivibile il salvataggio termina e l'errore è in `storage/logs/error.log`

🤖 Generated with [Claude Code](https://claude.com/claude-code)
MSG
)"
```

Expected: PR aperta su `wonder-image/app`, base `main`. Dopo il merge si passa al piano `2026-10-14-new-site-docs-ai.md`.
