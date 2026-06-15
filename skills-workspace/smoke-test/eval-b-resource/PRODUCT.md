# PRODUCT.md

## Register

`product` — il design serve il prodotto: lo strumento mostra il lavoro, non è esso stesso l'opera. (Alternativa non scelta: `brand`, dove il design è il prodotto.)

## Site type

`corporate`

> One of: `landing`, `corporate`, `blog`, `ecom`, `rsvp`.
> See `skills/wi-site/references/product-md.md#site_type-field--semantica` per cosa implica ciascun valore.

## Target users

PMI italiane che usano il sito per mostrare il proprio portfolio lavori a clienti e potenziali clienti.

## Brand personality

Concreto, professionale, affidabile. Niente fronzoli: il lavoro parla da sé.

## Voice / tone

Italiano, sobrio e diretto. Frasi corte, niente inglesismi di marketing ("boost", "unlock"). Terza persona neutra nei testi istituzionali.

## Design principles

1. Chiarezza prima dell'estetica. Se una scelta grafica complica la lettura, si toglie.
2. Mobile-first: il primo schermo è il telefono, il desktop viene dopo.
3. Riuso del design system `wonder-image/lib` — niente CSS parallelo, niente colori hardcoded.
4. Spazio al contenuto: i progetti del portfolio sono il messaggio, l'interfaccia li incornicia.

## Anti-references

- Niente animazioni gratuite o effetti decorativi.
- Niente claim altisonanti o gergo da agenzia.
- Niente layout che funzionano solo da desktop.

## Asset paths

- Logo SVG: `assets/{ASSETS_VERSION}/icons/logo.svg`
- Favicon: `assets/{ASSETS_VERSION}/icons/favicon.png`
- OG image default: `assets/{ASSETS_VERSION}/images/og-default.jpg`
