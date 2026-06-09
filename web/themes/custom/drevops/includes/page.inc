<?php

/**
 * @file
 * Page related functions.
 */

declare(strict_types=1);

/**
 * Generic page pre-processing.
 */
function _drevops_preprocess_page(array &$variables): void {
  $variables['header_is_sticky'] = civictheme_get_theme_config_manager()->load('components.header.is_sticky', FALSE);

  // The redesign renders the whole site dark: force the page, header and
  // footer themes so the canvas, chrome and every region read dark regardless
  // of per-node banner settings.
  $variables['theme'] = 'dark';
  $variables['header_theme'] = 'dark';
  $variables['footer_theme'] = 'dark';
}
