# Image zoom

Images placed in content open in a lightbox where a visitor can zoom in to read the detail. The page loads each image through an image style, and the lightbox fetches the original file only when a visitor opens it.

The [PhotoSwipe](https://www.drupal.org/project/photoswipe) module provides the formatter, the lightbox and the zoom. This site adds configuration, 2 small theme overrides and a cursor style, and no custom JavaScript.

## Where it is enabled

`core.entity_view_display.media.civictheme_image.embedded` is the display the `media_embed` filter uses for images placed in rich text. It formats `field_c_m_image` with the Photoswipe formatter:

| Setting | Value | Effect |
|---|---|---|
| Thumbnail image style | `wide` | The page loads the image scaled to 1090 wide and converted to WebP |
| Photoswipe modal image style | None (original image) | The lightbox shows the full-size upload, requested only when it opens |
| Image loading | Lazy | The page image loads when it scrolls into view |

Cards, banners, dividers and the image list are not zoomable. CivicTheme builds them from preprocess variables, and several already link somewhere else.

To make another image zoomable, select the Photoswipe formatter on the display that renders it. A display CivicTheme renders from preprocess variables ignores its formatters, which is why embedded images need the theme overrides below.

## How the figure renders the formatter

CivicTheme renders image media into `civictheme:figure` from preprocess variables and never prints the field, so a formatter chosen on the display changes nothing by default. 2 overrides in the `drevops` theme connect them for embedded images:

- `templates/media/media--civictheme-image--embedded.html.twig` passes the formatted `field_c_m_image` into the figure's `image` slot.
- `components/02-molecules/figure` replaces `civictheme:figure` to accept that slot and falls back to CivicTheme's own image when the slot is empty. Its stylesheet is a copy of CivicTheme's, as [Overriding a CivicTheme component](performance.md#overriding-a-civictheme-component) requires, with the image rule also matching `.ct-image`, because a slotted image does not carry the `ct-figure__image` class.

CivicTheme's field template prints no wrapper, so the formatter's `photoswipe-gallery` class never reaches the page. The module's `prepare-galleries.js` wraps each image in a fallback gallery element on page load, so every image opens on its own.

`assets/sass/_photoswipe.scss` gives every PhotoSwipe link a zoom-in cursor, so the image reads as zoomable rather than as a link to another page.

## Lightbox settings

`photoswipe.settings` keeps the module defaults except for 2 options:

- `secondaryZoomLevel` is `1`, so the zoom button and a click on the image show the original at 100%. The module default of `2.5` means 250% of the original, which for a 3000px wide screenshot zooms far past the point where text is sharp.
- `wheelToZoom` is on, so the mouse wheel zooms. With it off, only Ctrl and the wheel together do.

Change them at `/admin/config/media/photoswipe`, then export the configuration.

## The library

The PhotoSwipe JavaScript library is not on Packagist. `composer.json` declares it as a `package` repository pinned to the version the module expects, which Composer installs into `web/libraries/photoswipe`. That directory is not committed. Updating the library means changing the version in the repository entry and the `require` constraint together. The status report shows the installed version as "Photoswipe plugin".

The library is served from this origin, so the [content security policy](csp.md) needs no change. Keep the module's `enable_cdn` option off, so the lightbox does not depend on a third-party host.

Enabling the module still adds a scheme-less `cdnjs.cloudflare.com` to the policy's script and style sources. The `csp` module collects the external hosts of every library an enabled extension declares, and that includes PhotoSwipe's unused CDN library. The policy already allows `https://cdnjs.cloudflare.com`, so on the HTTPS site it admits nothing new.

On macOS, extracting the library archive reports a conflict, because it holds 2 file names that differ only by case. Composer then falls back to its own extractor and the install completes.

## Verifying a change

```bash
ahoy test-bdd -- tests/behat/features/image_zoom.feature
```

## Related

- [Front-end performance](performance.md) - image styles and the `wide` style the page image uses
- [Content security policy](csp.md) - why the library is served from this origin
