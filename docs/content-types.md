# Content types

This page describes how custom node bundles are added to this site, and the parts of the job that configuration alone does not cover.

## The content types on this site

| Machine name | Label | Provided by |
|---|---|---|
| `civictheme_page` | Page | CivicTheme |
| `civictheme_event` | Event | CivicTheme |
| `civictheme_alert` | Alert | CivicTheme |
| `blog` | Blog post | This project |

`civictheme_page` is the reference implementation. A new content type that is meant to look and behave like a page is built by cloning it, so the two bundles stay interchangeable and no field data can be orphaned if content is ever moved between them.

## Build the configuration on a running site, then export it

Write a throwaway script that creates the bundle by copying `civictheme_page`, run it with `ahoy drush php:script`, then `ahoy drush cex`. Do not hand-write the YAML.

Exporting is what produces canonical UUIDs and correct dependency arrays, so the configuration round-trips through `cex` cleanly instead of generating noise on the next export. The script is scratch work and belongs under `.artifacts/`; the committed artefact is the exported configuration.

Two things behave differently from how the exported YAML reads, and both will silently corrupt a clone:

**Layout Builder rewrites view displays saved through the entity API.** `core.entity_view_display.node.civictheme_page.default` has Layout Builder enabled. Saving a copy of it with `EntityViewDisplay::create(...)->save()` folds every formatter in the `content` region into the default layout section, turning a three-block layout into a thirteen-block one and rendering fields the source display never showed. Write these displays straight through the configuration factory instead, so the source is reproduced exactly:

```php
$data = \Drupal::config('core.entity_view_display.node.civictheme_page.default')->getRawData();
unset($data['_core']);
// Rewrite the bundle token throughout. This must walk the array recursively:
// the tokens live in nested values (the dependency lists, the id, the bundle,
// and each Layout Builder block plugin ID), and str_replace() applied to an
// array only touches its top-level elements.
$data = my_recursive_bundle_rewrite($data, 'civictheme_page', 'my_bundle');
$data['uuid'] = \Drupal::config('core.entity_view_display.node.my_bundle.default')->get('uuid') ?: \Drupal::service('uuid')->generate();
\Drupal::configFactory()->getEditable('core.entity_view_display.node.my_bundle.default')->setData($data)->save();
```

The bundle appears inside Layout Builder block plugin IDs (`field_block:node:civictheme_page:field_c_n_components`), so a verbatim copy would point the new bundle's layout at the Page bundle's field blocks. Reusing the target's existing UUID when one is already present keeps re-runs of the script from churning UUIDs on every export.

Verify the result by diffing the two displays with the bundle token normalised. They should differ only in `uuid` and `_core`.

**`list_string` allowed values are exposed in a simplified form.** `getSetting('allowed_values')` returns a value-keyed map (`['all' => 'Any', ...]`), not the list of `value`/`label` mappings the YAML shows. Append with `$values['my_bundle'] = 'My label';`, never `$values[] = ['value' => ..., 'label' => ...]`.

## The configuration a bundle needs

| Configuration | Required | Notes |
|---|---|---|
| `node.type.<bundle>` | Yes | Carries the `content_moderation` and `menu_ui` third-party settings, revision behaviour and submitted-by display. |
| `field.field.node.<bundle>.*` | Yes | Every field a page-like bundle needs already exists as a node-level field **storage** shared across bundles, so this attaches instances rather than creating storages. |
| `core.entity_form_display.node.<bundle>.default` | Yes | Includes the `field_group` third-party settings that build the tabbed authoring form. |
| `core.entity_view_display.node.<bundle>.default` | Yes | Layout Builder-driven. See the trap above. |
| `core.entity_view_display.node.<bundle>.civictheme_promo_card` | If listed | Needed for each card style the bundle should be renderable in, alongside `civictheme_navigation_card` and `civictheme_slider_slide`. |
| `pathauto.pattern.<bundle>` | Yes | Patterns are bundle-scoped, so there is no cross-bundle conflict and no weight tuning needed. Existing types use weight `-5`. |
| `workflows.workflow.civictheme_editorial` | Yes | Add the bundle to `type_settings.entity_types.node` and to the dependency list, otherwise the bundle has no moderation states. |
| `xmlsitemap.settings.node.<bundle>` | If public | A bundle is only in the sitemap when this object exists. See [Sitemap](sitemap.md). |
| `metatag.metatag_defaults.node__<bundle>` | Recommended | Mirrors the page defaults, which source the description from `field_c_n_summary`. |
| `language.content_settings.node.<bundle>` | Optional | The site is monolingual, so this only records the default. |
| `user.role.*` | Yes | See Permissions below. |
| `core.base_field_override.node.<bundle>.promote` | No | The CivicTheme types each carry one setting `promote` to `0`, but core already defaults the base field to `FALSE`, so omitting it changes nothing. |
| `captcha.captcha_point.node_<bundle>_form` | No | `captcha.settings` has `enable_globally: 0`, so a form with no point gets no challenge. The Page form's point exists but is disabled, so omitting it matches Page behaviour. |

## The theme layer

**This is the part configuration parity does not cover, and the easiest thing to miss.**

CivicTheme resolves full-view node preprocessing by bundle name. `_civictheme_preprocess_node__full()` looks for a function called `_civictheme_preprocess_node__<bundle>__full()` and calls it when it exists:

```php
$type_callback = '_civictheme_preprocess_node__' . $type . '__full';
if (function_exists($type_callback)) {
  $type_callback($variables);
}
```

That indirection is deliberate: each content type owns its own full-view theming. A bundle CivicTheme does not ship has no such function, so nothing fails loudly and the rendered page quietly loses:

- The table of contents, even with `field_c_n_show_toc` set.
- The topic tag list, even with topics selected and `field_c_n_hide_tags` unset.
- The `unset($variables['label'])` that stops the node title being repeated on revision pages.

A new content type must therefore supply its own `_civictheme_preprocess_node__<bundle>__full()`. Put it in a dedicated include under `web/themes/custom/drevops/includes/` and require it from `drevops.theme`; `blog.inc` is the worked example. The theme file is loaded before any preprocessing runs, so CivicTheme's `function_exists()` check finds it.

Keep each type's implementation self-contained rather than delegating to another type's preprocessor. Delegating couples the two bundles, so one cannot be changed without changing the other, and it silently inherits any upstream change to the borrowed function.

Do **not** put this logic in the theme's own `_drevops_preprocess_node__<view_mode>()` hook. That hook is for work that applies across bundles, and taking it over for one content type forces every later cross-bundle change to thread around a bundle-specific branch.

### Card view modes need no template

CivicTheme ships bundle-specific Twig templates such as `node--civictheme-page--civictheme-promo-card.html.twig`, but each is a one-line include of the same component as its generic counterpart (`node--civictheme-promo-card.html.twig`). A new bundle falls back to the generic template and renders identically, so no per-bundle template is needed unless the type genuinely needs different card markup.

## Permissions

Grant the new bundle's node permissions to whichever roles already hold the equivalents on `civictheme_page`. On this site that is `civictheme_content_author` and `civictheme_site_administrator`; `civictheme_content_approver` holds no per-bundle node permissions, so it gains none.

The reliable way to do this is to read each role's existing permissions and substitute the bundle name, rather than listing permission strings by hand.

## Making a type selectable in automated lists

Add the bundle to the allowed values of `field.storage.paragraph.field_c_p_list_content_type`, keeping the existing options and their order.

Nothing else is required. The `civictheme_automated_list` view already takes the bundle as a contextual argument (`plugin_id: node_type`), and its exposed content-type filter has an empty value list, so a new bundle appears in both automatically.

## Migrating existing nodes onto a new bundle

This is deploy-hook work, so [Development](development.md) governs how it is written: hooks public and helpers private, `drupal_helpers` for the batching, one batched operation per hook, and per-item atomicity. What follows is only what is specific to changing a node's bundle.

Drupal treats a loaded entity's bundle as immutable, so there is no entity-API route. Deleting and recreating each node would discard node IDs, revisions and paragraph references, which breaks aliases and revision history. The bundle has to be changed in storage.

The bundle is stored in three places:

- `node.type`
- `node_field_data.type`
- The `bundle` column of every dedicated field table, both `node__field_*` and `node_revision__field_*`

`node_revision` and `node_field_revision` have **no** bundle column on this site, so there is nothing to update there. Derive the field table list from the entity table mapping at runtime rather than naming the tables, so a field added to either bundle later cannot be missed:

```php
$table_mapping = $storage->getTableMapping();
$storage_definition = $definition->getFieldStorageDefinition();
$table_mapping->getDedicatedDataTableName($storage_definition);
$table_mapping->getDedicatedRevisionTableName($storage_definition);
```

Take the union of both bundles' field definitions, so a field attached to the source bundle but not the target cannot leave rows stranded under the old bundle where the data becomes unreadable.

### What a storage-level change does not do

No entity hooks fire, so anything derived from the bundle has to be repaired explicitly in the same hook:

- **XML sitemap.** The `xmlsitemap` table records each link's bundle in its `subtype` column. `do_base_deploy_rebuild_xmlsitemap()` would correct it, but deploy hooks run once per environment and will not re-run, so update the rows directly.
- **Search index.** Mark the migrated items updated on every index that has the `entity:node` datasource, otherwise the indexed documents keep describing the nodes under the old bundle.
- **Caches.** Invalidate the `node:<nid>` tags along with `node_list` and the `node_list:<bundle>` tags for both the old and new bundle, and reset the entity storage cache.

Path aliases need no special handling **provided no node is ever re-saved**: pathauto only regenerates on save, so hand-edited aliases survive untouched. This is why the migration must not loop over nodes calling `save()`.

### Repointing paragraphs that reference the bundle

Automated lists that target the old bundle need their `field_c_p_list_content_type` updated. Saving the paragraph is not sufficient: a host node references one specific paragraph revision through `target_revision_id`, and that is not necessarily the paragraph's current revision, so a save can leave the host still rendering the old value. Update the value across every revision of the paragraph instead, in both the field data and field revision tables.

Older revisions want the new value too. Left on the old bundle they render an empty list, because the migration has removed the content the old query matched.

Scope the paragraph query narrowly. Matching every list that targets the old bundle will also catch lists that legitimately still point there.

### Idempotency

Let the queries be the guard. A query for nodes still on the old bundle carrying the marker that defines the migration set matches nothing after a successful run, so a repeat deployment is a no-op with no extra bookkeeping.

Because the node migration and the paragraph repointing are batched, they are separate hooks and cannot share a transaction. If the second fails, the lists are left pointing at the old bundle and render empty until `drush deploy:hook` is run again, which completes them. Both are idempotent, so re-running is always safe.

## Testing

Cover the authoring form, the alias pattern, the moderation states, and the rendered output. See [Testing](testing.md) for conventions.

Assert the **theme-layer** output explicitly, not just that configuration exists. Topic tags (`.ct-tag-list`) and the table of contents (`[data-table-of-contents-anchor-selector]`) are produced by the per-bundle preprocessor described above, and a bundle missing that function still passes every configuration-level check while rendering neither. Cover both states of each toggle, so a test cannot pass by asserting the absence of something that is never present.

Migration behaviour that depends on real content cannot be asserted against a clean test database. Verify it by restoring a production database, running the deploy hook, and checking node IDs, aliases, revision counts and moderation states individually.
