<?php

/**
 * @file
 * DrevOps Callout paragraph component.
 */

declare(strict_types=1);

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;

/**
 * Implements template_preprocess_paragraph().
 *
 * CivicTheme renders the callout title through civictheme_get_field_value(),
 * which strips the design's accent markup (the gradient-underlined closing
 * word in the CTA heading). Re-read the raw title, keep a safe inline set and
 * render it as markup so the accent survives.
 */
function drevops_preprocess_paragraph__civictheme_callout(array &$variables): void {
  $paragraph = $variables['paragraph'];

  if (!$paragraph->hasField('field_c_p_title') || $paragraph->get('field_c_p_title')->isEmpty()) {
    return;
  }

  $raw = (string) $paragraph->get('field_c_p_title')->value;

  $variables['title'] = Markup::create(Xss::filter($raw, ['span', 'em', 'strong', 'br']));
}
