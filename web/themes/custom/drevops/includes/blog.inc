<?php

/**
 * @file
 * Blog post node functions.
 */

declare(strict_types=1);

use Drupal\node\NodeInterface;

/**
 * Pre-process Blog post nodes.
 *
 * CivicTheme resolves full-view node preprocessing by bundle name, calling
 * `_civictheme_preprocess_node__<bundle>__full()` when such a function exists,
 * so each content type owns its own full-view theming.
 */
function _civictheme_preprocess_node__blog__full(array &$variables): void {
  $node = $variables['node'] ?? NULL;

  if (!$node instanceof NodeInterface) {
    return;
  }

  // The page title is rendered by the page template, so leaving the node label
  // in place would repeat it on revision pages.
  unset($variables['label']);

  _drevops_node_add_table_of_contents($node, $variables);
  _drevops_node_add_topic_tags($node, $variables);
}
