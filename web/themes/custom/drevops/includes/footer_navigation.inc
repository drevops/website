<?php

/**
 * @file
 * Footer navigation block theme alterations.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_block__HOOK() for footer navigation.
 */
function drevops_preprocess_block__menu_block__civictheme_footer(array &$variables): void {
  // Labels the nav landmark, so screen readers can tell it apart from the
  // header navigation.
  $variables['name'] = (string) t('Footer');
  $variables['modifier_class'] = trim(($variables['modifier_class'] ?? '') . ' ct-navigation--footer');
}
