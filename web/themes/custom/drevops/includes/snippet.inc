<?php

/**
 * @file
 * Snippet paragraph component.
 */

declare(strict_types=1);

/**
 * Implements template_preprocess_paragraph().
 *
 * CivicTheme renders the snippet summary as a teaser, trimming it to a short
 * configurable length. The design's snippet-based sections (the homepage
 * services, "why" and process lists, and the services "approach" list) show
 * the full description, so restore the untrimmed summary value here. Snippets
 * with a naturally short summary (the stats and trust grids) are unaffected.
 */
function drevops_preprocess_paragraph__civictheme_snippet(array &$variables): void {
  $summary = civictheme_get_field_value($variables['paragraph'], 'field_c_p_summary', TRUE);

  if (!empty($summary)) {
    $variables['summary'] = $summary;
  }
}
