# Front-end performance

This document describes what the site does to keep pages light and stable while they load. Meta tags and structured data are covered in [SEO](seo.md).

## Images always go through an image style

An image style does two things here: it caps the pixel dimensions, and it converts to WebP. Both matter, because the source files are large brand renders and a 4K PNG hero is several megabytes.

| Style | Used for |
|---|---|
| `banner_background` | The banner background: scaled to 1920 wide, converted to WebP |
| `banner_featured` | The banner featured image: cropped to 1090x818 around the focal point, converted to WebP |
| `civictheme_*` | Cards, campaigns and slides: cropped by the CivicTheme sizes, converted to WebP |
| `divider` | The divider graphic: scaled to fit within 1090x480, converted to WebP |
| `image_list` | An image in the image list: scaled to fit within 320x160, converted to WebP |
| `wide` | An image placed in content and rendered as a figure |
| `social_share` | Share cards, deliberately **not** converted, because some social crawlers still handle WebP badly |

CivicTheme resolves several of these with no image style at all, which yields the URL of the original upload. Four preprocessors here re-resolve them: `_drevops_banner_apply_image_styles()` for the two banner images, `drevops_preprocess_paragraph__divider()` for the divider, `_drevops_preprocess_paragraph__paragraph_field__images()` for the image list, and `drevops_preprocess_media__civictheme_image()` for the figure. The banner one covers the block field and the node field, because the node's value wins when both are set.

The featured image sits in a box 40% of the viewport wide and no more than 600px tall, filled with `object-fit: cover`. Its ratio moves with the viewport and with how tall the banner's own content makes it, so the browser trims a different part of the image on every screen. A focal point crop puts the subject at the centre of the derivative, which is the part `cover` keeps whichever way it trims.

`drevops_preprocess_media__civictheme_image()` also clears the width and height CivicTheme measured on the source file, because they describe a different image once a style has resized it.

When adding an image style that renders photographic content, copy the `image_convert` effect from `image.style.large.yml`. A style with only a crop effect ships whatever format was uploaded.

## Images carry their own dimensions

Without `width` and `height` a browser cannot reserve space for an image, so every image on the page moves the content under it as it arrives.

The image component accepts both, but nothing that includes it passes them: the props are built from a media entity, which carries neither. `components/01-atoms/image/image.twig` overrides the CivicTheme component and fills them in through the `do_image_dimensions()` Twig function.

That function works from the source file and the style's own transform rather than measuring the derivative, so the numbers are right even on the request that generates that derivative for the first time. Vectors have no raster size, so their ratio is read from the `viewBox`; only the ratio matters, since CSS decides the rendered size.

Components are included from Twig rather than rendered through a render element, so there is no preprocess step and no `#pre_render` to hook. A Twig function called from the template is the only interception point.

## Overriding a CivicTheme component

Drupal matches components by name within an extension, so a same-named component in this theme is a new component rather than an override. The `replaces:` key in the `.component.yml` is what makes it an override:

```yaml
name: Image
replaces: civictheme:image
```

Without it the theme's version is simply never used, and the rendered markup keeps saying `data-component-id="civictheme:image"`.

**Copy the component's stylesheet along with its template.** A component's library is built from the files sitting in its own directory, so once `replaces:` points rendering at the override, the original's `.scss` is no longer part of what loads. An override carrying only a `.twig` renders the right markup with none of the component's CSS, which reads as the component being unstyled rather than as a missing file. `promo` and `pagination` both have one; `image` and `mobile-navigation-trigger` do not.

Checking the markup is not enough to catch this, because the markup is correct. Compare a computed style against the same element on production, or look at the page.

## Fonts are served from this origin

Lexend and Rubik ship in `assets/fonts/` and are declared in `components/00-base/fonts/fonts.scss`. Nothing is fetched from `fonts.googleapis.com`, which is why the CSP no longer allows it.

The editing area needs its own arrangement to hold that line. CKEditor 5 merges the base theme's `ckeditor5-stylesheets` into this theme's list, `.info.yml` has no override for that key, and CivicTheme's build of the editor stylesheet opens with two `@import` statements pointing at Google Fonts. `LibraryInfoAlterHook` drops the base theme's files from that list; `dist/styles.editor.css` is this theme's build of the same partials, with the self-hosted faces in place of the imports, and contributes every selector the base theme's copy did.

Leaving those imports in place cost more than a blocked request. A stylesheet whose `@import` is blocked fires `error` rather than `load`, so an Ajax response that attached the editor stylesheets reported the aggregate as unloadable and abandoned the commands queued behind it.

They are declared by hand rather than through CivicTheme's `$ct-fonts` map, because the generator that map feeds emits one `@font-face` per weight and supports neither the variable weight ranges these faces ship as nor the `unicode-range` and `font-display` descriptors a self-hosted face needs. The map still names the families, with an empty `types` list so it emits nothing.

Only the Latin subsets are preloaded, by `_drevops_attach_font_preloads()`. The extended subsets are needed by a small minority of pages, and a preload the page does not use is a wasted request.

Replacing a face means replacing the `woff2` files and the `unicode-range` values together. The ranges are Google's own subsetting boundaries and are what keeps a visitor reading only Latin text from downloading the extended file.

## The banner background is preloaded

The banner paints its background from CSS, so the browser cannot discover the file until the stylesheet has been fetched and parsed. On the pages carrying one, that background is the largest contentful paint.

`PageAttachmentsHook` emits a `rel="preload"` for it. The URL has to be the one the stylesheet asks for, or the file is fetched twice: the preload resolves the same image style, and skips the whole thing when the file has no derivative.

It runs only on the routes listed in that hook's `BANNER_ROUTES`, plus any route carrying the `_preview_link_route` option, which are the pages that draw a banner: the canonical route, a revision, the latest version, and a preview link. The edit form, the delete confirmation and the revision list all carry a node parameter and resolve the same background without ever rendering it, and a preload the page does not use is a wasted request for a full-width derivative.

## Verifying a change

```bash
ahoy test-kernel -- --filter=ImageDimensionsExtensionTest
```

For the rendered end, check that a page's images carry `width` and `height`, that no request goes to `fonts.googleapis.com`, and that image URLs contain `/styles/`. Lighthouse under mobile emulation is the quickest way to see the three metrics these affect: largest contentful paint, cumulative layout shift, and total byte weight.

## Related

- [SEO](seo.md) - meta tags, share cards and structured data
- [Sitemap](sitemap.md) - XML sitemap coverage and generation
