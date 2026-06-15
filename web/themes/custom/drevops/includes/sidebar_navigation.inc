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
  // The site renders dark, so the sidebar follows the dark theme.
  $variables['theme'] = CivicthemeConstants::THEME_DARK;
}
