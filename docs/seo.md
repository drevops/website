# SEO and social sharing

This document describes the meta tags, share cards and structured data the site publishes. The XML sitemap is covered separately in [Sitemap](sitemap.md).

## Modules

| Module | Role |
|---|---|
| `metatag` | Meta tag defaults per entity type and bundle, plus a per-node override field (`field_n_metatags`) |
| `metatag_open_graph` | `og:*` tags read by Facebook, LinkedIn and Slack |
| `metatag_twitter_cards` | `twitter:*` tags read by X |
| `metatag_dc` | Dublin Core tags |
| `schema_metatag` | Renders JSON-LD structured data through the same metatag defaults |
| `schema_organization`, `schema_web_site`, `schema_article`, `schema_web_page` | The Schema.org types this site publishes |
| `pathauto` | Readable, stable URL aliases |
| `redirect` | Keeps old URLs working after an alias changes |
| `redirect_404` | Records 404 paths at `/admin/config/search/redirect/404` so real lost traffic can be turned into redirects |
| `robotstxt` | Serves `/robots.txt` from configuration |
| `xmlsitemap` | See [Sitemap](sitemap.md) |

Deliberately not installed: `yoast_seo` (a heavy analyser on every node form), `seo_checklist` (an admin checklist with no runtime effect), `linkchecker` (a crawler with ongoing cron cost) and `metatag_hreflang` (meaningless while the site is single-language).

## What every page publishes

- `title`, `description` and `canonical_url`, from the metatag defaults.
- The full Open Graph set: `og:site_name`, `og:type`, `og:url`, `og:title`, `og:description`, `og:image` with `og:image:width`, `og:image:height` and `og:image:alt`.
- The full Twitter Card set: `twitter:card`, `twitter:site`, `twitter:title`, `twitter:description`, `twitter:image` and `twitter:image:alt`.
- A JSON-LD `@graph` carrying `Organization` and `WebSite`, plus `WebPage` with its `BreadcrumbList` on nodes and `Article` on blog posts.

`twitter:card` is `summary_large_image` site-wide. Any other value makes X render a small square thumbnail, which is why the share image is produced at 1200x630.

## Where each value comes from

Configuration holds the values that genuinely differ between pages:

| Metatag default | Holds |
|---|---|
| `global` | Site-level values, the Twitter card type and handle, and the `Organization` and `WebSite` structured data |
| `front` | The front page's canonical and Open Graph URL |
| `node` | Node title, description and URL |
| `node__blog` | `og:type: article`, the article timestamps and the `Article` structured data |
| `node__civictheme_page`, `node__project` | The summary field the description is taken from |
| `node` | `WebPage` and its breadcrumb, alongside the node title, description and URL |

The social **title and description are not configured**. `MetatagsAlterHook` derives them from the `title` and `description` tags, and the Twitter pair from the Open Graph pair. This keeps one source of truth for the wording, and it is also the only way those tags reach the front page: `metatag_get_default_tags()` treats the front page, 403 and 404 as special pages and stops after the global and special defaults, never reading the entity or bundle defaults.

The social **image is not configured either**, because a metatag default that resolves to nothing is dropped rather than falling back to its parent, and most pages have no thumbnail. `MetatagsAlterHook` resolves it instead:

1. The node's `field_c_n_thumbnail` media, rendered through the `social_share` image style (1200x630, focal point aware), with the media's alt text.
2. Otherwise `web/modules/custom/do_base/assets/social-share.jpg`, with the site name as alt text.

An editor who sets `og:image` by hand on a node keeps it: the hook leaves the whole image family alone in that case, and emits no width, height or alt, because it cannot know them for a file it did not choose.

A thumbnail is passed over in favour of the fallback when no image toolkit can derive it (the image field accepts SVG) or when the file is recorded in the database but absent from the environment.

The `Article` image in the structured data is filled from the same resolved value, so it carries the same fallbacks. It is set as an `ImageObject` rather than a bare URL because `SchemaImageObjectBase::output()` drops any value without a `url` key.

## Title and description length

A search result shows roughly 60 characters of the title and 155 of the description, and cuts whatever is past that. Two things keep the tags inside those bounds.

`MetatagsAlterHook::trimDescriptions()` cuts `description`, `og:description` and `twitter:description` to 155 characters on a word boundary. It runs on `hook_metatags_attachments_alter()` rather than `hook_metatags_alter()`, because tokens are still unreplaced when the tags themselves are altered: at that point a description is the literal `[node:field_c_n_summary:value]` and there is nothing to measure. All three tags get the same treatment so the wording stays identical wherever it appears.

That is a floor, not a substitute for writing to length. Pages whose derived wording was too long, too short, or missing carry an explicit value in `field_n_metatags`, written once by `do_base_deploy_set_seo_metatags()`. The hook replaces a tag only when the value it finds falls outside those bounds, so wording an editor writes later is left alone and a repeat deployment is a no-op.

The titles it writes carry qualifiers the visible heading does not need: a page headed `GovCMS` is titled `GovCMS Development and Migration`, because the heading has a whole page for context and a search result has one line. They use `[site:name]` rather than a literal brand, matching the `node` default they replace.

## Image assets

Both live in `web/modules/custom/do_base/assets/` and are generated from the theme's brand assets:

- `social-share.jpg` (1200x630) - the fallback share card: the brand wordmark over the dark navy page background, under the site's positioning line with its accent phrase picked out in coral. It is rendered from `.artifacts/tmp/social-card.html`, which carries the same colour tokens, Lexend weights and letter spacing as the branding styleguide, so the card matches the rest of the brand rather than approximating it.
- `logo.png` (600x142) - the `Organization` logo in the structured data. Schema.org requires a raster image, and the brand logo exists only as SVG.

Replacing either file is the whole change if a designed asset arrives later. The dimensions of `social-share.jpg` are stated in `MetatagsAlterHook::fallbackImage()` rather than measured, so they must be kept in step with the file.

## Known limits

- `Article.author` is the organisation rather than a person. The site has no per-author profiles to point at.
- `twitter:creator` is unset for the same reason.
- No `robots` meta tag default is set. Indexing is governed by `/robots.txt`, and `do_base` adds `noindex, nofollow` to preview link pages only.

## Why publication dates come from `created`

`article:published_time` and `Article.datePublished` are taken from the node's `created` timestamp, not from the moment a draft was first published. That is deliberate: `created` is the editorially-controlled "Authored on" date, and it is the field `views.view.civictheme_automated_list` sorts the blog by, so it is already the date the site presents as a post's date. Structured data is expected to agree with what a visitor sees, and a separate first-publication timestamp would disagree with the visible ordering.

Changing this would mean adding a field populated on the first published transition and backfilling existing posts - a content-modelling change, not an SEO one.

## Verifying a change

```bash
ahoy test-bdd -- --tags=@metatags
ahoy test-functional -- --filter=SocialCardTest
```

The Behat feature asserts the rendered tags against the real configuration and theme; the PHPUnit test covers the resolver's fallbacks, which are the paths that fail silently in production. External validators worth a look after a change that touches structured data: the [Schema Markup Validator](https://validator.schema.org/) and [Google's Rich Results Test](https://search.google.com/test/rich-results).

## Related

- [Sitemap](sitemap.md) - XML sitemap coverage and generation
- [Development](development.md) - function visibility conventions the hook follows
