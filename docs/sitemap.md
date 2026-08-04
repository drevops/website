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
