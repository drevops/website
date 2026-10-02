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
  $variables['type'] = 'inline';
}
