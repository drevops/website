<?php

/**
 * @file
 * Blog theme alterations.
 */

declare(strict_types=1);

use Drupal\node\NodeInterface;

/**
 * Add a body class on the blog landing page.
 */
function _drevops_preprocess_html__blog(array &$variables): void {
  $node = \Drupal::routeMatch()->getParameter('node');

  if (!$node instanceof NodeInterface) {
    return;
  }

  // Tag the blog landing page so the listing's first card can be styled as a
  // featured post. Identify it by the node the "/blog" alias resolves to,
  // rather than the alias the request arrived on, so it holds regardless of
  // which alias (or language) was used to reach the node.
  $blog_path = \Drupal::service('path_alias.manager')->getPathByAlias('/blog');

  if ($blog_path === '/node/' . $node->id()) {
    $variables['attributes']['class'][] = 'ct-page--blog';
  }
}
