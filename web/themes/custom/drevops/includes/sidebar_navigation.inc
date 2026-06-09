<?php

/**
 * @file
 * Sidebar navigation block theme alterations.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConstants;

/**
 * Implements template_preprocess_block__HOOK() for sidebar navigation.
 *
 * The redesign renders the whole site dark, so the sidebar navigation follows
 * the dark theme rather than the light default this override previously forced.
 */
function drevops_preprocess_block__menu_block__civictheme_sidebar_navigation(array &$variables): void {
  $variables['theme'] = CivicthemeConstants::THEME_DARK;
}
