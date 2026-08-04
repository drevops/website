# FAQs

For common questions and answers, see
[Vortex FAQs](https://www.vortextemplate.com/docs/development/faqs).

## Project-specific FAQs

### Why does the "Publish content" bulk action fail with "No access to execute"?

Every node bundle is under the `civictheme_editorial` content moderation workflow. Core forbids the `Publish content` and `Unpublish content` bulk actions on moderated entities, because publication status is owned by the workflow rather than by the `status` field. The failure is by design and applies to every account, including uid 1.

Use `Publish latest revision`, `Unpublish current revision` or `Archive current revision` instead. These apply a workflow transition and are available to the `civictheme_site_administrator` and `civictheme_content_approver` roles.

### Why are `simple_sitemap` and `gin_toolbar` in `web/modules/contrib` when nothing uses them?

Neither module is installed - the site serves `/sitemap.xml` with `xmlsitemap` and renders its admin menu with the core Navigation module - and neither package is listed in `composer.json`. They arrive as dependencies of packages the site does want: `drupal/civictheme` requires `drupal/simple_sitemap`, and `drupal/gin` requires `drupal/gin_toolbar`. Composer keeps both in `composer.lock` and on disk for as long as those parents declare them.

Leave them alone. Forcing them out with a `replace` entry would hide both from `composer audit` and from Drupal's security advisories, and would turn a future parent-package requirement into a missing class at runtime instead of a resolver error at install time.
