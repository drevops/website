<?php

/**
 * @file
 * Snippet paragraph component.
 */

declare(strict_types=1);

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;

/**
 * Implements template_preprocess_paragraph().
 *
 * CivicTheme renders the snippet summary as a teaser, trimming it to a short
 * configurable length and stripping any markup. The design's snippet-based
 * sections (the homepage services, AI, "why" and process lists, and the
 * services "approach" list) show the full description, and the AI list links to
 * the Responsible AI policy. Read the raw summary so it is untrimmed and keep a
 * safe set of inline tags (the policy link, emphasis), rendered as markup.
 */
function drevops_preprocess_paragraph__civictheme_snippet(array &$variables): void {
  $paragraph = $variables['paragraph'];

  if (!$paragraph->hasField('field_c_p_summary') || $paragraph->get('field_c_p_summary')->isEmpty()) {
    return;
  }

  $raw = (string) $paragraph->get('field_c_p_summary')->value;

  $variables['summary'] = Markup::create(Xss::filter($raw, ['a', 'em', 'strong']));
}
