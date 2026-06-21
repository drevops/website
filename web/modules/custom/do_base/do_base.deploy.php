<?php

/**
 * @file
 * Deploy functions called from drush deploy:hook.
 *
 * @see https://www.drush.org/latest/deploycommand/
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeColorManager;

/**
 * Rebuild CivicTheme colour stylesheets from the imported colour configuration.
 */
function do_base_deploy_civictheme_colors(): string {
  if (!class_exists(CivicthemeColorManager::class)) {
    return 'CivicTheme is not available; skipped colour stylesheet rebuild.';
  }

  // CivicTheme compiles the colour palette into a generated stylesheet that is
  // rebuilt only when the theme settings form is saved. A configuration import
  // updates the stored palette but leaves the previous stylesheet on disk, so
  // purging it forces a rebuild from the imported configuration on the next
  // request - keeping the rendered colours in sync with the committed config.
  // A failed purge must not abort the deployment, so the error is reported
  // rather than thrown.
  try {
    \Drupal::classResolver(CivicthemeColorManager::class)->invalidateCache();
  }
  catch (\Throwable $exception) {
    return 'Failed to purge CivicTheme generated colour stylesheets: ' . $exception->getMessage();
  }

  return 'Purged CivicTheme generated colour stylesheets for rebuild from configuration.';
}
