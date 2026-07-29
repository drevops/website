<?php

/**
 * @file
 * Node related functions.
 */

declare(strict_types=1);

use Drupal\node\NodeInterface;

/**
 * Implements template_preprocess_node().
 */
function _drevops_preprocess_node__full(array &$variables): void {
  $node = $variables['node'] ?? NULL;

  if (!$node instanceof NodeInterface) {
    return;
  }

  // CivicTheme dispatches its full-view preprocessing by bundle name, so a
  // bundle it does not ship renders without a table of contents and without
  // topic tags. Blog posts are meant to be interchangeable with pages, so they
  // reuse the page preprocessor rather than restating its behaviour here.
  if ($node->bundle() === 'blog') {
    _civictheme_preprocess_node__civictheme_page__full($variables);
  }
}

/**
 * Implements template_preprocess_node().
 */
function _drevops_preprocess_node__civictheme_promo_card(array &$variables): void {
  // @see https://www.drupal.org/project/civictheme/issues/3415756
  _drevops_preprocess_paragraph__node_field__date($variables);
}

/**
 * Pre-process for Date node field.
 */
function _drevops_preprocess_paragraph__node_field__date(array &$variables): void {
  $node = $variables['node'] ?? civictheme_get_field_value($variables['paragraph'], 'field_c_p_reference', TRUE);
  if ($node) {
    $variables['date'] = _civictheme_node_get_updated_date($node, 'civictheme_short_date');
    $variables['date_iso'] = _civictheme_node_get_updated_date($node, 'c');
  }
}
