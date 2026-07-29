<?php

/**
 * @file
 * Administration navigation provided by the core Navigation module.
 */

declare(strict_types=1);

use Drupal\Core\Site\Settings;

/**
 * Implements template_preprocess_html().
 */
function drevops_preprocess_html(array &$variables): void {
  // Hide the administration navigation. Useful for theming while logged in as
  // an administrator.
  if (Settings::get('hide_admin_navigation')) {
    unset($variables['page_top']['navigation']);
    unset($variables['page_top']['top_bar']);
  }
}
