<?php

/**
 * @file
 * Skip link theme alterations.
 */

declare(strict_types=1);

use Drupal\civictheme\CivicthemeConstants;

/**
 * Pre-process the skip link for the html template.
 */
function _drevops_preprocess_html__skip_link(array &$variables): void {
  // CivicTheme themes the skip link per page and leaves it light on content
  // pages; pin it to dark so this chrome matches the site-wide dark scheme.
  $variables['skip_link_theme'] = CivicthemeConstants::THEME_DARK;
}
