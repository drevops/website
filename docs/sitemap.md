# XML sitemap

This site standardises on [`drupal/xmlsitemap`](https://www.drupal.org/project/xmlsitemap), which is the module the Vortex template ships in its default module set. Staying on the template default keeps this part of the stack aligned with Vortex so it does not have to be re-decided on every template update.

## What is published

The sitemap is served at `/sitemap.xml`.

| Item | Setting |
|---|---|
| Front page | Priority `1.0`, change frequency `daily` |
| `civictheme_page` nodes | Priority `0.5` (omitted from the output, as `0.5` is the format's assumed default) |
| `blog` nodes | Priority `0.5` (omitted from the output, as `0.5` is the format's assumed default) |
| `project` nodes | Priority `0.5` (omitted from the output, as `0.5` is the format's assumed default) |
| `civictheme_event` nodes | Not listed |
| `civictheme_alert` nodes | Not listed |
| Taxonomy terms, menu links, users | Not listed |

A bundle is included only when an `xmlsitemap.settings.<entity_type>.<bundle>` config object exists for it, so adding a content type to the sitemap means adding that config, not just creating the type.

Only `xmlsitemap` itself is enabled. The `xmlsitemap_custom` and `xmlsitemap_engines` submodules are deliberately left off - the front page is covered natively by `frontpage_priority` and `frontpage_changefreq`, and pinging search engines on every change is not wanted.

## The host the URLs carry

Cron regenerates the sitemap, and cron has no request to take a host from, so `xmlsitemap` keeps its own base URL. Left unset it falls back to a state value seeded at install time from whichever host ran the installer, and state is not exported configuration, so that value never travels with the code and never shows up in a diff.

`web/sites/default/includes/modules/settings.xmlsitemap.php` states it instead. It has to match the host in the canonical tags and in `robots.txt`, because anything else makes every URL in the sitemap a redirect to the host the site actually serves.

## Front page de-duplication

`system.site:page.front` points at a node that has no path alias, so that node would otherwise be listed twice: once as `/` and once under its internal path. `do_base_xmlsitemap_link_alter()` drops the second entry, because two sitemap URLs serving identical content read as duplicate content to search engines.

## How generation runs

Links are indexed and the sitemap files regenerated on cron. The generated files are written to the public files directory (`public://xmlsitemap/`), not held in the database, so that directory must be writable and shared across all web containers. **If production sitemaps ever go stale, check this first.**

`do_base_deploy_rebuild_xmlsitemap()` rebuilds the sitemap during `drush deploy`. A freshly installed `xmlsitemap` has an empty link table, so without it a deployment would serve a near-empty `/sitemap.xml` until the next cron run.

To rebuild by hand:

```bash
ahoy drush xmlsitemap:rebuild
```

## Related configuration

`composer.json` patches `seckit` because its JS/CSS/noscript protection breaks `sitemap.xml`. That patch applies to the route rather than to any particular sitemap module, so it is required regardless of which module serves the route.

`composer.json` also patches `xmlsitemap` itself, with the merge request from [issue 3562043](https://www.drupal.org/project/xmlsitemap/issues/3562043). Drupal 11 ships jQuery 4, which no longer provides `jQuery.trim()`, and the tablesorter plugin bundled with the module still calls it, so sorting on the styled page throws before any click handler is bound. The patch drops jQuery and tablesorter from `xsl/xmlsitemap.xsl` and rewrites `xsl/xmlsitemap.xsl.js` as vanilla JavaScript. That merge request is unmerged, so the diff is stored at `patches/xmlsitemap-sort-jquery4-3562043.patch` rather than referenced by URL, which would let the applied code change without a commit.

The styled page is for humans - search engines read the raw XML and never execute XSLT or JavaScript. Chrome [disables XSLT by default](https://developer.chrome.com/docs/web-platform/deprecating-xslt) in version 158 on 17 November 2026, keeps it reachable to sites covered by an origin trial or an enterprise policy until version 176 on 17 August 2027, and other engines have signalled the same direction. Once it is gone the browser shows the plain XML tree, and neither the stylesheet nor this patch has any effect.
