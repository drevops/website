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
}
