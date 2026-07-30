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

  if (civictheme_get_field_value($node, 'field_c_n_show_toc')) {
    array_unshift($variables['content'], [
      '#theme' => 'civictheme_table_of_contents',
      '#position' => 'prepend',
      '#anchor_selector' => 'h2',
      '#title' => t('On this page'),
      '#scope_selector' => '.ct-layout__main',
    ]);
  }

  if (civictheme_get_field_value($node, 'field_c_n_hide_tags')) {
    return;
  }

  $topics = civictheme_get_referenced_entity_labels($node, 'field_c_n_topics', $variables);

  if (empty($topics)) {
    return;
  }

  $variables['content'][] = [
    '#theme' => 'civictheme_tag_list',
    '#tags' => array_map(static fn($label): array => ['content' => (string) $label], $topics),
    '#vertical_spacing' => 'both',
  ];
}
