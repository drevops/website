<?php

/**
 * @file
 * Page related functions.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConstants;

/**
 * Generic page pre-processing.
 */
function _drevops_preprocess_page(array &$variables): void {
  $variables['header_is_sticky'] = civictheme_get_theme_config_manager()->load('components.header.is_sticky', FALSE);

  // The page component reads these top-level theme props directly, but
  // CivicTheme's page preprocess only sets the nested page.* equivalents - so
  // without this the dark redesign's page wrapper, header and footer fall back
  // to the component's light default.
  $variables['theme'] = CivicthemeConstants::THEME_DARK;
  $variables['header_theme'] = CivicthemeConstants::THEME_DARK;
  $variables['footer_theme'] = CivicthemeConstants::THEME_DARK;

  // Minimal footer: copyright (site name) and a single public contact email.
  $variables['site_name'] = \Drupal::config('system.site')->get('name');
  $variables['contact_email'] = 'info@drevops.com';
}
