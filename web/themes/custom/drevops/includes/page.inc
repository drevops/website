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

  // Force dark so a per-node light banner cannot flip the page chrome.
  $variables['theme'] = CivicthemeConstants::THEME_DARK;
  $variables['header_theme'] = CivicthemeConstants::THEME_DARK;
  $variables['footer_theme'] = CivicthemeConstants::THEME_DARK;

  // Minimal footer: copyright (site name) and a single public contact email.
  $variables['site_name'] = \Drupal::config('system.site')->get('name');
  $variables['contact_email'] = 'info@drevops.com';
}
