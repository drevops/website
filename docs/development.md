# Development agreements

Coding agreements for this project. They are conventions this codebase holds itself to, not general Drupal advice, so follow them over whatever a generic example would do.

## Function visibility

PHP has no visibility keywords for procedural functions, so this project marks intent with a leading underscore.

- A function that Drupal or Drush **invokes by name** is public and carries no underscore. That means hook implementations: `hook_deploy_NAME()`, `hook_update_N()`, `hook_preprocess_HOOK()`, and the theme's own dispatched preprocessors.
- **Every other function is private and starts with an underscore.** Helpers that exist only to support a hook are never part of the module's or theme's surface.

```php
// Public: the deploy registry finds and runs this by name.
function do_base_deploy_migrate_blog_posts(?array &$sandbox = NULL): ?string { ... }

// Private: only the hook above calls it.
function _do_base_blog_migrate_node(NodeInterface $node): void { ... }
```

Beyond signalling intent, the underscore keeps helpers clear of the naming patterns Drupal and Drush scan for, so a helper can never be mistaken for a hook and run on its own.

## Reach for `drupal_helpers` before hand-rolling

`drupal_helpers` is a static facade over the operations deploy and update hooks perform. **Read `web/modules/contrib/drupal_helpers/README.md` before writing a hook** rather than assuming what exists. It has an "Available methods" table listing every helper (`Alias`, `Block`, `Config`, `Display`, `Entity`, `Field`, `Menu`, `Module`, `Redirect`, `Role`, `Term`, `Translation`, `User`) with the signature and an example for each.

Never hand-roll an operation a helper already covers. The helpers are idempotent, they handle edge cases the raw APIs do not (a module already uninstalled, a module whose code is gone but is still recorded in the database), and they are sandbox-aware for batched work.

### Batch anything that iterates entities

Take the sandbox as the hook's argument and pass it to the helper, so the work is spread across requests instead of risking a timeout:

```php
function do_base_deploy_something(?array &$sandbox = NULL): ?string {
  $query = \Drupal::entityQuery('node')->condition('type', 'article');

  return Helper::entity($sandbox, 10)->batchQuery($query, static function (NodeInterface $node): void {
    _do_base_do_the_work($node);
  }, status: Reporter::UPDATED);
}
```

Points worth knowing before you write one:

- Type the parameter `?array &$sandbox = NULL` to match the nullable reference `Helper::entity()` takes. Typing it `array` makes PHPStan reject the call.
- The helper returns `NULL` while batching and a summary string once finished. Return its result straight from the hook.
- Lower the batch size from the default of 50 when each item is expensive. The blog migration uses 10 because every article rewrites rows in more than forty field tables.
- `batchQuery()` runs the query **once** and stashes the IDs in the sandbox, so the callback must not depend on the query still matching the item it is handed.

### One batched operation per hook

The sandbox uses fixed keys (`items`, `total`, `current`), so a single hook cannot run two batched operations. Split multi-step work into separate hooks instead of trying to share a sandbox.

Deploy hooks run in alphabetical order within a module, so name them so they run in the order you need. The blog work is two hooks for this reason: `do_base_deploy_migrate_blog_posts` then `do_base_deploy_repoint_blog_lists`.

### Prefer per-item atomicity over one large transaction

A sandbox spans several requests, so a transaction opened in one of them cannot wrap the whole run. Wrap each item's work in its own transaction instead. Combined with a query that only matches unprocessed items, that makes an interrupted batch safe to resume rather than leaving the dataset half-changed.

### Report what happened

Record outcomes with `Helper::reporter()` and end the hook with `return Helper::report();` so the deployment log says what actually happened on each environment. When the batch helpers already report each item, return their result instead of `Helper::report()`.

The one exception is installing or uninstalling a module: that rebuilds the container and replaces the shared reporter with an empty one, so return the message `Helper::module()` itself returns.

### `drupal_helpers` must exist before an update hook uses it

`drush deploy` runs `updatedb` before `cim`, so a module enabled only through exported configuration is not yet available to `hook_update_N()`. An update hook that needs the helpers must install the module first with `\Drupal::service('module_installer')->install(['drupal_helpers'])`, which is the only place the core installer is preferred over `Helper::module()`.

Deploy hooks (`hook_deploy_NAME()`) run after `cim` and need no such bootstrap. They also see configuration the same deployment just imported, so a deploy hook never needs to import configuration itself.

## Running ad-hoc code

Never pass code inline through `drush php:eval`, stdin, or a heredoc. Write it to a file under `.artifacts/` and run `ahoy drush php:script <path>`. Committed, vetted scripts may use `php:eval` for static, non-dynamic operations.

## Related

- [Content types](content-types.md) - adding custom node bundles, including the theme layer configuration parity does not cover
- [Testing](testing.md) - PHPUnit and Behat conventions
