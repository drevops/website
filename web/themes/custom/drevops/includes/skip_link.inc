<?php

/**
 * @file
 * Skip link theme alterations.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConstants;

/**
 * Implements hook_preprocess_HOOK() for the html template.
 */
function drevops_preprocess_html(array &$variables): void {
  // CivicTheme themes the skip link per page and leaves it light on content
  // pages; pin it to dark so this chrome matches the site-wide dark scheme.
  $variables['skip_link_theme'] = CivicthemeConstants::THEME_DARK;
}
