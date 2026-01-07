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
  $variables['theme'] = CivicthemeConstants::THEME_LIGHT;
}
