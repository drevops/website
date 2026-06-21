<?php

/**
 * @file
 * Sidebar navigation block theme alterations.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConstants;

/**
 * Implements template_preprocess_block__HOOK() for sidebar navigation.
 */
function drevops_preprocess_block__menu_block__civictheme_sidebar_navigation(array &$variables): void {
  // Pin the sidebar navigation to the dark scheme so it matches the site-wide
  // dark mode rather than the CivicTheme default light scheme.
  $variables['theme'] = CivicthemeConstants::THEME_DARK;
}
