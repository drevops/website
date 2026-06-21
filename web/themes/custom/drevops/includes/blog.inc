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
  // Tag the blog landing page so the listing's first card can be styled as a
  // featured post. Resolved from the path alias rather than a fixed node id so
  // it holds across environments.
  $node = \Drupal::routeMatch()->getParameter('node');

  if (!$node instanceof NodeInterface) {
    return;
  }

  $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());

  if ($alias === '/blog') {
    $variables['attributes']['class'][] = 'ct-page--blog';
  }
}
