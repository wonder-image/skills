# Configurable Header Navigation

Use this reference when building or refactoring a reusable frontend header in a Wonder site. It documents a site-level pattern: the navigation data and component implementation live under `custom/`, while markup and behavior reuse `wonder-image/lib` primitives wherever possible.

## Source of truth and ownership

- Keep desktop and mobile navigation in one recursive `custom/config/navigation.php` array.
- Keep header appearance defaults in the top-level header component. Do not introduce a separate `custom/config/header.php` only for colors, borders, blur, or glass.
- Put the theme in `custom/view/components/frontend/layout/<theme>/`. A theme may be named for its visual behavior, such as `floating`; do not reuse a directory name already owned by another theme.
- Split rendering into small components instead of duplicating link, dropdown, and mobile markup.
- Keep user-facing labels in `lang/{locale}/*.json`, resolve them with `__t(...)`, prefer `__r(...)` for named routes, and use `__u(...)` only for free paths.

## Navigation item contract

Each item uses the same shape at every depth:

| Key | Meaning |
| --- | --- |
| `key` | Stable translation key below `components.navigation.*`. |
| `route` | Optional named route. Preferred over `url`. |
| `url` | Optional free path resolved through `__u(...)`, or an absolute URL when `external` is true. |
| `query` | Optional associative array appended with RFC 3986 query encoding. |
| `external` | Optional boolean. External links keep their URL and receive safe new-tab attributes. |
| `children` | Optional recursive array of navigation items. |
| `dropdown` | Optional desktop presentation: `type` and, for mega menus, `content`. |
| `image` | Optional image used only by desktop mega cards. |
| `placement` | Set to `action` to render an item in the desktop action area while keeping it in the mobile navigation flow. |
| `variant` | Optional existing button variant for an action item; defaults to `primary`. |
| `mobileAsButton` | Optional action-item boolean; defaults to `true`. Set to `false` to render the action as a normal mobile nav link. |
| `visible` | Reserved for an explicit site authorization or audience filter. Do not claim it is enforced until the component implements the filter. |

Do not hardcode a CTA such as “Contact” separately in the header. Represent it as a navigation item with `placement => 'action'`; this keeps it available on mobile and gives the configuration one source of truth.

## Desktop dropdown selection

Desktop dropdown behavior is selected by data, not by a separate template or JavaScript branch:

- No `dropdown` configuration means `classic`.
- `dropdown.type = mega` enables a mega menu.
- A mega menu without `dropdown.content` uses `list`.
- Set `dropdown.content = card` only when cards are explicitly wanted.
- Normalize unknown values defensively to `classic` for `type` and `list` for mega `content`.
- Mobile ignores the desktop presentation and always renders the same tree as a cascade.

```php
return [
    // Classic is the default: direct children become a standard dropdown.
    [
        'key' => 'services',
        'children' => [
            ['key' => 'consulting', 'route' => 'services.consulting'],
            ['key' => 'support', 'route' => 'services.support'],
        ],
    ],

    // Mega + list is the default mega presentation.
    [
        'key' => 'areas',
        'dropdown' => ['type' => 'mega'],
        'children' => [
            [
                'key' => 'property',
                'route' => 'property.index',
                'children' => [
                    ['key' => 'for_sale', 'route' => 'property.index', 'query' => ['contract' => 'sale']],
                    ['key' => 'sold', 'route' => 'property.sold'],
                ],
            ],
        ],
    ],

    // Cards are an explicit presentation override.
    [
        'key' => 'projects',
        'dropdown' => ['type' => 'mega', 'content' => 'card'],
        'children' => [
            [
                'key' => 'residential',
                'route' => 'projects.residential',
                'image' => 'images/navigation/residential.webp',
                'children' => [],
            ],
        ],
    ],

    // An action remains part of the mobile menu.
    [
        'key' => 'contact',
        'route' => 'contact',
        'placement' => 'action',
        'variant' => 'primary',
        'mobileAsButton' => false,
    ],
];
```

## Rendering semantics

- A classic desktop dropdown renders the parent trigger and its direct children with the lib dropdown component.
- A mega dropdown treats direct children as groups. Their own children become the group links.
- `list` renders those groups with grid, spacing, and text utilities. It is the default because it needs less markup and works well without imagery.
- `card` wraps each group in the existing box/card primitive and may render `image`; do not show those images in the mobile cascade.
- Mobile recursively traverses every `children` level, independent of whether desktop uses classic, mega list, or mega cards.

Avoid inferring the desktop presentation only from tree depth. The same data shape can need a different visual treatment, so `dropdown.type` is the explicit switch. Tree depth still determines how content is grouped and how mobile levels are generated.

## Recommended component boundaries

A reusable theme should normally have these responsibilities:

| Component | Responsibility |
| --- | --- |
| `header.php` | Load and filter navigation once, normalize dropdown options once, split primary/action items in one pass, apply appearance variables, and compose desktop/mobile regions. |
| `nav-item.php` | Select link, action, classic trigger, mega trigger, or mobile trigger. |
| `nav-link.php` | Own label translation, route/path resolution, query strings, and external-link attributes. |
| `nav-dropdown.php` | Render the desktop mega container and its groups. |
| `nav-dropdown-group.php` | Render a mega group as `list` or `card`. |
| `nav-mobile-level.php` | Recursively render one mobile cascade level plus its Back action. |

Use `\Wonder\View\View::component(...)` for composition. Pass normalized options down instead of rereading configuration in each partial.

## Appearance contract

Accept an optional `appearance` array in the top-level component and merge it with theme defaults. A practical contract is:

```php
$appearance = [
    'effect' => 'glass-frosted', // none, glass, glass-frosted, glass-blur, blur-1 … blur-5
    'background' => 'transparent',
    'border' => 'transparent',
    'borderWidth' => '0px',
    'divider' => 'var(--dropdown-border-color)',
    'text' => 'var(--black-color)',
    'icon' => 'var(--secondary-color)',
];
```

Map values to component-scoped CSS custom properties and escape the resulting style attribute. Allow only known lib effect classes. Use existing site variables instead of hardcoded color literals. `background` and an effect are independent: the header may use a solid/token background, blur/glass, both, or neither.

## Reuse `wonder-image/lib`

Read [`style-and-lib.md`](style-and-lib.md) and inspect `wonder-image/lib/MANIFEST.json` before adding CSS or JavaScript.

- Classic dropdowns should reuse `.wi-dropdown-btn`, `.wi-dropdown-list`, `.wi-dropdown-item`, and the existing visible-state class such as `.wi-show` when its behavior fits.
- Mega cards should reuse the existing `.wi-box`, button/badge, grid, spacing, radius, shadow, image, and typography utilities.
- Mega lists should use grid, spacing, divider, and typography utilities; do not add a second custom layout system just to make `list` different from `card`.
- Reuse breakpoint visibility classes for desktop/mobile regions.
- Keep custom CSS limited to header geometry, staged transitions, theme-scoped custom properties, and behavior the lib does not provide.
- Keep custom JavaScript limited to coordinating desktop hover/focus state and mobile cascade state. Do not reimplement lib primitives.

Never define a new `.wi-*` class in the site. If the needed primitive is truly absent and should be shared, that work belongs in `wonder-image/lib` and requires an explicit request.

## Interaction and accessibility invariants

Desktop:

- Open a dropdown when its trigger is hovered or receives focus.
- Keep it open while the pointer or focus is inside either the trigger region or its dropdown.
- Close it after both pointer and focus leave that combined region; a short close delay may bridge the physical gap between trigger and panel.
- Preserve click/touch fallback, close competing dropdowns, and close on Escape.
- Keep `aria-expanded` on the trigger and `aria-hidden` on the controlled panel synchronized.

Mobile:

- Render one cascade level at a time. A parent with children uses a right chevron; the child level starts with a translated Back action and left chevron.
- Keep the menu panel visually connected to the header surface and prevent parent overflow from clipping a child level.
- On close, hide/fade the current level content first, then collapse the panel upward. Do not leave a chevron or empty white panel visible between stages.
- Keep the action item inside the same grid/gap flow; `mobileAsButton` changes presentation, not placement.
- Toggle `inert`, `aria-hidden`, the menu button label, and scroll locking together.
- Honor `prefers-reduced-motion`.

## Performance and portability

- Build desktop and mobile from the same already-filtered array; never duplicate navigation data.
- Normalize `type`, `content`, action placement, and stable IDs once in `header.php`.
- Initialize the header once per root element. Prefer event delegation or a bounded listener set and clean up timers on state changes.
- Keep `list` versus `card` a PHP/markup decision. JavaScript should manage open/closed state, not visual content mode.
- Scope custom selectors and variables to the theme so it can move into another Wonder site without leaking styles.
- Avoid site-specific names, colors, routes, and translation copy in the reusable component implementation.

## Validation matrix

Before handing off the header:

1. Run `php -l` on `custom/config/navigation.php` and every touched PHP component.
2. Verify a parent with no `dropdown` renders a classic desktop dropdown.
3. Verify `['type' => 'mega']` renders a list mega menu by default.
4. Verify `['type' => 'mega', 'content' => 'card']` renders cards and optional images.
5. Verify desktop hover and keyboard focus can move between trigger and panel without an accidental close; verify Escape.
6. Verify mobile uses the recursive cascade for both classic and mega data, including Back at every nested level.
7. Verify `mobileAsButton => false` renders the action as a normal nav link with the same grid gap as sibling items.
8. Verify `aria-expanded`, `aria-hidden`, `inert`, focus behavior, scroll locking, and reduced motion.
9. Check the browser console and test at phone, tablet, and desktop widths.
10. Do not run Forge or npm rebuilds unless framework/runtime integration or the installed lib version actually changed.
