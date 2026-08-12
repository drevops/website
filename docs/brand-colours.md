# Brand colours

The brand defines six colours. https://brand.drevops.com/ is the source of truth for them, and for the ratio they are meant to appear in: white and Cool White 56%, Navy 28%, Teal 12%, Coral 4%.

| Name | Hex | Role |
|---|---|---|
| Navy | `#152235` | Primary ground: structure, chrome, headers, footers, dark panels |
| Deep Navy | `#030d1e` | The deepest ground layer |
| Cool White | `#eff6ff` | The light surface |
| Teal | `#1e7582` | Accent on light backgrounds: links, kickers, rules, ticks, the single call to action |
| Bright Cyan | `#96e7f4` | Replaces Teal on Navy backgrounds |
| Coral | `#cd5b43` | Problems, warnings, "before" states, risk labels |

Steps between those six come from the ramps in `static-prototype/framework.css` (`--color-primary-*` around Navy, `--color-secondary-*` around Teal and Bright Cyan, `--color-tertiary-*` around Coral, plus `pass`, `fail`, `warn` and `info` ramps for status). They are the generated ramps around the brand anchors, so a shade that the brand does not name is taken from there rather than invented.

## Where the palette lives

CivicTheme renders from 18 named palette slots per theme. The site carries them in two places, and both have to be edited together.

| File | What it is |
|---|---|
| `config/default/drevops.settings.yml` | The source of truth. `CivicthemeColorManager` compiles `colors.palette` into `public://css-variables.drevops.css` as `--ct-color-{light,dark}-{slot}` |
| `web/themes/custom/drevops/components/variables.base.scss` | The Storybook fallback. Component SCSS compiles `ct-color-light('x')` to `var(--ct-color-light-x, <fallback>)`, and Storybook has no generated stylesheet, so the fallback is what it renders |

The config uses underscores (`background_light`), the SCSS map uses hyphens (`background-light`).

`colors.brand` never reaches CSS. It seeds the Brand colors fieldset in the theme settings form, where a JS handler recomputes the palette fields from a brand swatch. `use_brand_colors` is `0`, which hides that fieldset, so the recompute cannot overwrite the palette.

## The palette

| Slot | Light | | Dark | |
|---|---|---|---|---|
| `heading` | `#152235` | Navy | `#ffffff` | White |
| `body` | `#2b394d` | Navy 8 | `#eff6ff` | Cool White |
| `background_light` | `#ffffff` | White | `#2b394d` | Navy 8 |
| `background` | `#eff6ff` | Cool White | `#152235` | Navy |
| `background_dark` | `#c8d9f3` | Navy 2 | `#030d1e` | Deep Navy |
| `border_light` | `#c8d9f3` | Navy 2 | `#5b6a80` | Navy 6 |
| `border` | `#8fa0b8` | Navy 4 | `#425166` | Navy 7 |
| `border_dark` | `#425166` | Navy 7 | `#2b394d` | Navy 8 |
| `interaction_text` | `#ffffff` | White | `#030d1e` | Deep Navy |
| `interaction_background` | `#1e7582` | Teal | `#96e7f4` | Bright Cyan |
| `interaction_hover_text` | `#ffffff` | White | `#030d1e` | Deep Navy |
| `interaction_hover_background` | `#045a65` | Teal 7 | `#e1fbff` | Cyan 1 |
| `interaction_focus` | `#cd5b43` | Coral | `#ff9c86` | Coral 3 |
| `highlight` | `#1e7582` | Teal | `#96e7f4` | Bright Cyan |
| `information` | `#326971` | Info 8 | `#9cccd3` | Info 4 |
| `warning` | `#a37a00` | Amber 8 | `#ffcf3d` | Amber 5 |
| `error` | `#b7202e` | Red 7 | `#ea858f` | Red 4 |
| `success` | `#208436` | Green 8 | `#8ce3a0` | Green 4 |

`highlight` is the stripe accent: the bar on callouts, blockquotes, accordions and cards, the active primary-navigation border, and the table-of-contents hover marker. It is also what the eyebrow kicker and rich-text table captions use, through `assets/sass/_eyebrow.scss`.

`warning` uses the amber ramp rather than Coral. The brand assigns Coral to warnings, but that is about authored content - a risk label, a "before" column - and the four status slots sit next to each other in form and message chrome, where amber, red, green and teal-grey stay distinguishable in a way that two adjacent reds do not.

## Every slot is set, none is derived

CivicTheme can derive all 18 slots from three brand anchors, using formulas such as `heading = brand1|shade,60`. Those filters mix with pure black and pure white, which desaturates: deriving from Teal and Cool White produces a grey `#38484a` body and a grey `#bfc5cc` surface rather than steps along the brand's blue ramp. Every slot is therefore written out.

The consequence is that changing a brand colour changes nothing on its own. A rebrand means editing the 18 values in both files.

## Contrast

Every pair the palette puts together clears WCAG AA. The tight ones are worth knowing about:

| Pair | Ratio |
|---|---|
| Teal on white | 5.36:1 |
| Teal on Cool White | 4.92:1 |
| White on the Teal button | 5.36:1 |
| Bright Cyan on Navy | 11.5:1 |
| Deep Navy on the Bright Cyan button | 13.9:1 |
| `#a37a00` warning on white | 3.93:1 |

The warning amber is only ever a border, which needs 3:1 rather than 4.5:1. Moving it to a text role means darkening it to `#705400` first.

`interaction_focus` cannot clear 3:1 against both white and the Teal button, because the two requirements point in opposite directions for any single colour. CivicTheme's shipped default has the same limitation. The chosen values are the ones that work on the page backgrounds, which is where the offset ring actually sits.

## Applying a change

The compiled stylesheet is written once and then left alone, so a config change on its own leaves the old palette on disk:

```bash
ahoy drush cim -y
ahoy drush cr
```

`ThemeColorSubscriber` purges the stylesheet when the colour config is saved, and `do_base_deploy_refresh_theme_colors()` purges it on every deploy, so an import is enough. For the SCSS side:

```bash
ahoy cli npm run build --prefix web/themes/custom/drevops
```

Check both themes after a change. Light and dark are separate palettes and a slot can be right in one and wrong in the other.

## Related

- [Front-end performance](performance.md) - image styles, self-hosted fonts and layout stability
- [Content types](content-types.md) - adding a bundle and the theme layer it needs
